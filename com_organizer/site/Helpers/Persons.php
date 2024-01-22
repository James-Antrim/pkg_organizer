<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

use Joomla\Database\ParameterType;
use stdClass;
use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input, User};
use THM\Organizer\Tables\{Persons as Table};

/**
 * Provides general functions for person access checks, data retrieval and display.
 */
class Persons extends Scheduled implements Selectable
{
    use Active;
    use Suppressed;

    public const COORDINATES = 1, TEACHES = 2;

    protected static string $resource = 'person';

    /**
     * Retrieves person entries from the database
     * @return stdClass[]  the persons who hold courses for the selected program and pool
     * @todo implement the plugin around this again
     */
    public static function byProgramOrPool(): array
    {
        $query = DB::getQuery();
        $query->select('DISTINCT p.id, p.forename, p.surname')
            ->from(DB::qn('#__organizer_persons', 'p'))
            ->innerJoin(DB::qn('#__organizer_subject_persons', 'sp'), DB::qc('sp.personID', 'p.id'))
            ->innerJoin(DB::qn('#__organizer_curricula', 'c'), DB::qc('c.subjectID', 'sp.subjectID'))
            ->order(DB::qn(['p.surname', 'p.forename']));

        $programID = Input::getInt('programID', self::NONE);
        $poolID    = Input::getInt('poolID', self::NONE);

        $boundarySet = $poolID > 0 ? Pools::rows($poolID) : Programs::rows($programID);

        if ($boundarySet) {

            $count = 0;
            $left  = DB::qn('c.lft');
            $right = DB::qn('c.rgt');
            $where = '';

            foreach ($boundarySet as $boundaries) {

                $phLeft = ":left$count";
                $query->bind($phLeft, $boundaries['lft'], ParameterType::INTEGER);
                $phRight = ":right$count";
                $query->bind($phRight, $boundaries['rgt'], ParameterType::INTEGER);

                $where .= $count === 0 ?
                    "(($left >= $phLeft AND $right <= $phRight)" : " OR ($left >= $phLeft AND $right <= $phRight)";

                $count++;
            }

            $query->where($where . ')');
        }

        DB::setQuery($query);

        if (!$persons = DB::loadObjectList()) {
            return [];
        }

        foreach ($persons as $value) {
            $value->name = empty($value->forename) ? $value->surname : $value->surname . ', ' . $value->forename;
        }

        return $persons;
    }

    /**
     * Generates a default person text based upon organizer's internal data
     *
     * @param   int   $personID      the person's id
     * @param   bool  $excludeTitle  whether the title should be excluded from the return value
     *
     * @return string  the default name of the person
     */
    public static function defaultName(int $personID, bool $excludeTitle = false): string
    {
        $person = new Table();
        $person->load($personID);
        $return = '';

        if ($person->id) {
            $title    = ($person->title and !$excludeTitle) ? "$person->title " : '';
            $forename = $person->forename ? "$person->forename " : '';
            $surname  = $person->surname;
            $return   = $title . $forename . $surname;
        }

        return $return;
    }

    /**
     * Checks for multiple person entries (roles) for a subject and removes the lesser
     *
     * @param   array &$list  the list of persons with a role for the subject
     *
     * @return void  removes duplicate list entries dependent on role
     */
    private static function ensureUnique(array &$list): void
    {
        $keysToIds = [];
        foreach ($list as $key => $item) {
            $keysToIds[$key] = $item['id'];
        }

        $valueCount = array_count_values($keysToIds);
        foreach ($list as $key => $item) {
            $unset = ($valueCount[$item['id']] > 1 and $item['role'] > 1);
            if ($unset) {
                unset($list[$key]);
            }
        }
    }

    /**
     * Retrieves the person's forenames.
     *
     * @param   int  $personID  the person's id
     *
     * @return string  the default name of the person
     */
    public static function forename(int $personID): string
    {
        $person = new Table();
        $person->load($personID);

        return $person->forename ?: '';
    }

    /**
     * Retrieves the persons associated with a given subject, optionally filtered by role.
     *
     * @param   int   $subjectID  the subject's id
     * @param   int   $roleID     represents the person's role for the subject
     * @param   bool  $multiple   whether multiple results are desired
     * @param   bool  $unique     whether unique results are desired
     *
     * @return array|array[]  an array of person data
     */
    public static function getDataBySubject(
        int $subjectID,
        int $roleID = 0,
        bool $multiple = false,
        bool $unique = true
    ): array
    {
        $aliased  = DB::qn(['u.id'], ['userID']);
        $selected = DB::qn(['p.id', 'p.surname', 'p.forename', 'p.title', 'p.username', 'sp.role', 'code']);
        $query    = DB::getQuery();
        $query->select(array_merge($selected, $aliased))
            ->from(DB::qn('#__organizer_persons', 'p'))
            ->innerJoin(DB::qn('#__organizer_subject_persons', 'sp'), DB::qc('sp.personID', 'p.id'))
            ->leftJoin(DB::qn('#__users', 'u'), DB::qc('u.username', 'p.username'))
            ->where(DB::qn('sp.subjectID') . ' = :subjectID')->bind(':subjectID', $subjectID, ParameterType::INTEGER)
            ->order(DB::qn('surname'));

        if ($roleID) {
            $query->where(DB::qn('sp.role') . ' = :roleID')->bind(':roleID', $roleID, ParameterType::INTEGER);
        }

        DB::setQuery($query);

        if ($multiple) {
            if (!$persons = DB::loadAssocList()) {
                return [];
            }

            if ($unique) {
                self::ensureUnique($persons);
            }

            return $persons;
        }

        return DB::loadAssoc();
    }

    /**
     * Gets the organizations with which the person is associated
     *
     * @param   int  $personID  the person's id
     *
     * @return string[] the organizations with which the person is associated id => name
     */
    public static function getOrganizationNames(int $personID): array
    {
        $query = DB::getQuery();
        $tag   = Application::getTag();
        $query->select(DB::qn("o.shortName_$tag", 'name'))
            ->from(DB::qn('#__organizer_organizations', 'o'))
            ->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qn('a.organizationID') . ' = ' . DB::qn('o.id'))
            ->where(DB::qn('personID') . ' = :personID')
            ->bind(':personID', $personID, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::loadColumn();
    }

    /**
     * Checks whether the user has an associated person resource by their username, returning the id of the person
     * entry if existent.
     *
     * @param   int  $userID  the user id if empty the current user is used
     *
     * @return int the id of the person entry if existent, otherwise 0
     */
    public static function getIDByUserID(int $userID = 0): int
    {
        if (!$user = User::instance($userID)) {
            return 0;
        }

        $person = new Table();
        $person->load(['username' => $user->username]);

        return $person->id ?: 0;
    }

    /**
     * @inheritDoc
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::resources() as $person) {
            if ($person['active']) {
                $name     = $person['surname'];
                $forename = trim($person['forename']);
                $name     .= $forename ? ", $forename" : '';

                $options[] = HTML::option($person['id'], $name);
            }
        }

        return $options;
    }

    /**
     * @inheritDoc
     */
    public static function resources(): array
    {
        $organizationID = Input::getInt('organizationID');
        if ($organizationIDs = $organizationID ? [$organizationID] : Input::getFilterIDs('organization')) {
            foreach ($organizationIDs as $key => $organizationID) {
                if (!Can::view('organization', $organizationID)) {
                    unset($organizationIDs[$key]);
                }
            }
        }
        else {
            $organizationIDs = Can::manageTheseOrganizations();
        }

        $query = DB::getQuery();
        $query->select('DISTINCT ' . DB::qn('p') . '.*')
            ->from(DB::qn('#__organizer_persons AS p'))
            ->where(DB::qn('p.active') . ' = 1')
            ->order(DB::qn(['p.surname', 'p.forename']));

        $wherray = [];

        if (self::getIDByUserID() and $userName = User::userName()) {
            $wherray[] = DB::qn('p.username') . ' = :username';
            $query->bind(':username', $userName);
        }

        if (count($organizationIDs)) {
            $query->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.personID', 'p.id'));

            $where = DB::qn('a.organizationID') . ' IN (' . implode(',', $query->bindArray($organizationIDs)) . ')';

            if ($categoryID = Input::getInt('categoryID')) {
                $categoryIDs = [$categoryID];
            }

            $categoryIDs = empty($categoryIDs) ? Input::getIntCollection('categoryIDs') : $categoryIDs;
            $categoryIDs = empty($categoryIDs) ? Input::getFilterIDs('category') : $categoryIDs;

            if ($categoryIDs and $categoryIDs = implode(',', $query->bindArray($categoryIDs))) {
                $query->innerJoin(DB::qn('#__organizer_instance_persons', 'ip'), DB::qc('ip.personID', 'p.id'))
                    ->innerJoin(DB::qn('#__organizer_instance_groups', 'ig'), DB::qc('ig.assocID', 'ip.id'))
                    ->innerJoin(DB::qn('#__organizer_groups', 'g'), DB::qc('g.id', 'ig.groupID'));

                $where .= ' AND ' . DB::qn('g.categoryID') . " IN ($categoryIDs)";
                $where = "($where)";
            }

            $wherray[] = $where;
        }
        elseif ($organizationID) {
            $query->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.personID', 'p.id'))
                ->bind(':organizationID', $organizationID, ParameterType::INTEGER);
            $wherray[] = '(' . DB::qn('a.organizationID') . ' = :organizationID AND ' . DB::qn('p.public') . ' = 1) ';
        }

        if ($wherray) {
            $query->where('(' . implode(' OR ', $wherray) . ')');
            DB::setQuery($query);

            return DB::loadAssocList('id');
        }

        return [];
    }

    /**
     * Generates a preformatted person text based upon organizer's internal data
     *
     * @param   int   $personID  the person's id
     * @param   bool  $short     whether the person's forename should be abbreviated
     *
     * @return string  the default name of the person
     */
    public static function lastNameFirst(int $personID, bool $short = false): string
    {
        $person = new Table();
        $person->load($personID);
        $return = '';

        if ($person->id) {
            $return = $person->surname;

            if ($person->forename) {
                // Getting the first letter by other means can cause encoding problems with 'interesting' first names.
                $forename = $short ? mb_substr($person->forename, 0, 1) . '.' : $person->forename;
                $return   .= empty($forename) ? '' : ", $forename";
            }
        }

        return $return;
    }

    /**
     * Retrieves the person's public release status.
     *
     * @param   int  $personID  the person's id
     *
     * @return bool  the person's public release status
     */
    public static function public(int $personID): bool
    {
        $person = new Table();
        $person->load($personID);

        return $person->public;
    }

    /**
     * Function to sort persons by their surnames and forenames.
     *
     * @param   array &$persons  the persons array to sort.
     *
     * @return void
     */
    public static function sortByName(array &$persons): void
    {
        uasort($persons, function ($personOne, $personTwo) {
            if ($personOne['surname'] > $personTwo['surname']) {
                return 1;
            }
            if ($personOne['surname'] < $personTwo['surname']) {
                return -1;
            }

            return strcmp($personOne['forename'], $personTwo['forename']);
        });
    }

    /**
     * Function to sort persons by their roles.
     *
     * @param   array &$persons  the persons array to sort.
     */
    public static function sortByRole(array &$persons): void
    {
        uasort($persons, function ($personOne, $personTwo) {
            $roleOne = isset($personOne['role'][self::COORDINATES]);
            $roleTwo = isset($personTwo['role'][self::COORDINATES]);
            if ($roleOne or !$roleTwo) {
                return 1;
            }

            return -1;
        });
    }

    /**
     * Retrieves the person's surnames.
     *
     * @param   int  $personID  the person's id
     *
     * @return string  the default name of the person
     */
    public static function surname(int $personID): string
    {
        $person = new Table();
        $person->load($personID);

        return $person->surname ?: '';
    }

    /**
     * Returns the ids of the organizations where the user has previously been assigned as a teacher.
     * @return int[] the ids of the relevant organizations
     */
    public static function taughtOrganizations(): array
    {
        if (!$personID = self::getIDByUserID()) {
            return [];
        }

        $query = DB::getQuery();
        $query->select('DISTINCT a.organizationID')
            ->from(DB::qn('#__organizer_associations', 'a'))
            ->innerJoin(DB::qn('#__organizer_instance_groups', 'ig'), DB::qc('ig.groupID', 'a.groupID'))
            ->innerJoin(DB::qn('#__organizer_instance_persons', 'ipe'), DB::qc('ipe.id', 'ig.assocID'))
            ->where("ipe.personID = $personID")
            ->where("ipe.roleID = 1");
        DB::setQuery($query);

        return DB::loadIntColumn();
    }
}
