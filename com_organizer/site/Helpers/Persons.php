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

use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input};
use Joomla\Database\ParameterType;
use THM\Organizer\Tables;
use stdClass;

/**
 * Provides general functions for person access checks, data retrieval and display.
 */
class Persons extends Associated implements Selectable
{
    use Suppressed;

    // TODO move all person related constants here and use this class instead of redefining them
    private const COORDINATES = 1;

    protected static $resource = 'person';

    /**
     * Retrieves person entries from the database
     * @return stdClass[]  the persons who hold courses for the selected program and pool
     * @todo used by a plugin?
     */
    public static function byProgramOrPool(): array
    {
        $programID = Input::getInt('programID', -1);
        $poolID    = Input::getInt('poolID', -1);

        if ($poolID > 0) {
            $boundarySet = Pools::ranges($poolID);
        }
        else {
            $boundarySet = Programs::ranges($programID);
        }

        $query = DB::getQuery();
        $query->select('DISTINCT p.id, p.forename, p.surname')
            ->from('#__organizer_persons AS p')
            ->innerJoin('#__organizer_subject_persons AS sp ON sp.personID = p.id')
            ->innerJoin('#__organizer_curricula AS c ON c.subjectID = sp.subjectID')
            ->order('p.surname, p.forename');

        if (!empty($boundarySet)) {
            $where   = '';
            $initial = true;
            foreach ($boundarySet as $boundaries) {
                $where   .= $initial ?
                    "((c.lft >= '{$boundaries['lft']}' AND c.rgt <= '{$boundaries['rgt']}')"
                    : " OR (c.lft >= '{$boundaries['lft']}' AND c.rgt <= '{$boundaries['rgt']}')";
                $initial = false;
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
     * Checks for multiple person entries (roles) for a subject and removes the lesser
     *
     * @param   array &$list  the list of persons with a role for the subject
     *
     * @return void  removes duplicate list entries dependent on role
     */
    private static function ensureUnique(array &$list)
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
     * Retrieves the persons associated with a given subject, optionally filtered by role.
     *
     * @param   int   $subjectID  the subject's id
     * @param   int   $role       represents the person's role for the subject
     * @param   bool  $multiple   whether multiple results are desired
     * @param   bool  $unique     whether unique results are desired
     *
     * @return array|array[]  an array of person data
     */
    public static function getDataBySubject(
        int $subjectID,
        int $role = 0,
        bool $multiple = false,
        bool $unique = true
    ): array
    {
        $query = DB::getQuery();
        $query->select('p.id, p.surname, p.forename, p.title, p.username, u.id AS userID, sp.role, code')
            ->from('#__organizer_persons AS p')
            ->innerJoin('#__organizer_subject_persons AS sp ON sp.personID = p.id')
            ->leftJoin('#__users AS u ON u.username = p.username')
            ->where("sp.subjectID = $subjectID")
            ->order('surname');

        if ($role) {
            $query->where("sp.role = $role");
        }

        DB::setQuery($query);

        if ($multiple) {
            if (!$personList = DB::loadAssocList()) {
                return [];
            }

            if ($unique) {
                self::ensureUnique($personList);
            }

            return $personList;
        }

        return DB::loadAssoc();
    }

    /**
     * Generates a default person text based upon organizer's internal data
     *
     * @param   int   $personID      the person's id
     * @param   bool  $excludeTitle  whether the title should be excluded from the return value
     *
     * @return string  the default name of the person
     */
    public static function getDefaultName(int $personID, bool $excludeTitle = false): string
    {
        $person = new Tables\Persons();
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
     * Retrieves the person's surnames.
     *
     * @param   int  $personID  the person's id
     *
     * @return string  the default name of the person
     */
    public static function getForename(int $personID): string
    {
        $person = new Tables\Persons();
        $person->load($personID);

        return $person->forename ?: '';
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
     * Generates a preformatted person text based upon organizer's internal data
     *
     * @param   int   $personID  the person's id
     * @param   bool  $short     whether the person's forename should be abbreviated
     *
     * @return string  the default name of the person
     */
    public static function getLNFName(int $personID, bool $short = false): string
    {
        $person = new Tables\Persons();
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
     * Checks whether the user has an associated person resource by their username, returning the id of the person
     * entry if existent.
     *
     * @param   int  $userID  the user id if empty the current user is used
     *
     * @return int the id of the person entry if existent, otherwise 0
     */
    public static function getIDByUserID(int $userID = 0): int
    {
        if (!$user = Users::getUser($userID)) {
            return 0;
        }

        $query = DB::getQuery();
        $query->select('id')
            ->from('#__organizer_persons')
            ->where("username = '$user->username'");
        DB::setQuery($query);

        return DB::loadInt();
    }

    /**
     * @inheritDoc
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::getResources() as $person) {
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
    public static function getResources(): array
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

        $userName = '';
        if ($thisPersonID = self::getIDByUserID()) {
            $userName = Users::getUser()->username;
        }

        $query = DB::getQuery();
        $query->select('DISTINCT p.*')
            ->from('#__organizer_persons AS p')
            ->where('p.active = 1')
            ->order('p.surname, p.forename');

        $wherray = [];

        if ($thisPersonID) {
            $wherray[] = "p.username = '$userName'";
        }

        if (count($organizationIDs)) {
            $query->innerJoin('#__organizer_associations AS a ON a.personID = p.id');

            $where = 'a.organizationID IN (' . implode(',', $organizationIDs) . ')';

            if ($categoryID = Input::getInt('categoryID')) {
                $categoryIDs = [$categoryID];
            }

            $categoryIDs = empty($categoryIDs) ? Input::getIntCollection('categoryIDs') : $categoryIDs;
            $categoryIDs = empty($categoryIDs) ? Input::getFilterIDs('category') : $categoryIDs;

            if ($categoryIDs and $categoryIDs = implode(',', $categoryIDs)) {
                $query->innerJoin('#__organizer_instance_persons AS ip ON ip.personID = p.id')
                    ->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ip.id')
                    ->innerJoin('#__organizer_groups AS g ON g.id = ig.groupID');

                $where .= " AND g.categoryID in ($categoryIDs)";
                $where = "($where)";
            }

            $wherray[] = $where;
        }
        elseif ($organizationID) {
            $query->innerJoin('#__organizer_associations AS a ON a.personID = p.id');
            $wherray[] = "(a.organizationID = $organizationID and p.public = 1) ";
        }

        if ($wherray) {
            $query->where('(' . implode(' OR ', $wherray) . ')');
            DB::setQuery($query);

            return DB::loadAssocList('id');
        }

        return [];
    }

    /**
     * Retrieves the person's surnames.
     *
     * @param   int  $personID  the person's id
     *
     * @return string  the default name of the person
     */
    public static function getSurname(int $personID): string
    {
        $person = new Tables\Persons();
        $person->load($personID);

        return $person->surname ?: '';
    }

    /**
     * Function to sort persons by their surnames and forenames.
     *
     * @param   array &$persons  the persons array to sort.
     */
    public static function nameSort(array &$persons)
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
     * Retrieves the person's public release status.
     *
     * @param   int  $personID  the person's id
     *
     * @return bool  the person's public release status
     */
    public static function released(int $personID): bool
    {
        $person = new Tables\Persons();
        $person->load($personID);

        return $person->public;
    }

    /**
     * Function to sort persons by their roles.
     *
     * @param   array &$persons  the persons array to sort.
     */
    public static function roleSort(array &$persons)
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
            ->from('#__organizer_associations AS a')
            ->innerJoin('#__organizer_instance_groups AS ig ON ig.groupID = a.groupID')
            ->innerJoin('#__organizer_instance_persons AS ipe ON ipe.id = ig.assocID')
            ->where("ipe.personID = $personID")
            ->where("ipe.roleID = 1");
        DB::setQuery($query);

        return DB::loadIntColumn();
    }
}
