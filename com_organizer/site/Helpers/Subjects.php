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
use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input, User};
use THM\Organizer\Tables;

/**
 * Provides general functions for subject access checks, data retrieval and display.
 */
class Subjects extends Curricula
{
    protected static string $resource = 'subject';

    /**
     * Check if user one of the subject's coordinators.
     *
     * @param   int  $subjectID  the optional id of the subject
     * @param   int  $personID   the optional id of the person entry, defaults to current user
     *
     * @return bool true if the user is a coordinator, otherwise false
     */
    public static function coordinates(int $subjectID = 0, int $personID = 0): bool
    {
        if (!$personID = $personID ?: Persons::getIDByUserID(User::id())) {
            return false;
        }

        $coordinates = Persons::COORDINATES;
        $query       = DB::getQuery();
        $query->select('COUNT(*)')->from(DB::qn('#__organizer_subject_persons'))
            ->where(DB::qn('personID') . ' = :personID')->bind(':personID', $personID, ParameterType::INTEGER)
            ->where(DB::qn('role') . ' = :coordinates')->bind(':coordinates', $coordinates, ParameterType::INTEGER);

        if ($subjectID) {
            $query->where(DB::qn('subjectID') . ' = :subjectID')->bind(':subjectID', $subjectID, ParameterType::INTEGER);
        }

        DB::setQuery($query);

        return DB::loadBool();
    }

    /**
     * @inheritDoc
     */
    public static function documentableIDs(string $column = 'subjectID'): array
    {
        return parent::documentableIDs($column);
    }

    /**
     * Retrieves the event ID associated with the subject.
     *
     * @param   int  $subjectID  the id of the referencing subject
     *
     * @return int the id of the referenced event
     */
    private static function eventID(int $subjectID): int
    {
        $query = DB::getQuery();
        $query->select(DB::qn('eventID'))->from(DB::qn('#__organizer_subject_events'))
            ->where(DB::qn('subjectID') . ' = :subjectID')->bind(':subjectID', $subjectID, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::loadInt();
    }

    /**
     * Retrieves the left and right boundaries of the nested program or pool
     * @return array|array[]
     */
    private static function filterRanges(): array
    {
        if (!$programRanges = Programs::rows(Input::getInt('programID'))) {
            return [];
        }

        if ($poolRanges = Pools::rows(Input::getInt('poolID'))
            and self::included($poolRanges, $programRanges)) {
            return $poolRanges;
        }

        return $programRanges;
    }

    /**
     * Retrieves the subject name
     *
     * @param   int   $resourceID  the table id for the subject
     * @param   bool  $withNumber  whether to integrate the subject code directly into the name
     *
     * @return string the subject name
     */
    public static function getName(int $resourceID = 0, bool $withNumber = false): string
    {
        if (!$resourceID = $resourceID ?: Input::getID()) {
            return '';
        }

        $query = DB::getQuery();
        $tag   = Application::getTag();

        $select = DB::qn(["abbreviation_$tag", 'code', "fullName_$tag"], ['abbreviation', 'subjectNo', 'name']);
        $query->select($select)->from(DB::qn('#__organizer_subjects'))
            ->where(DB::qn('id') . ' = :subjectID')->bind(':subjectID', $resourceID, ParameterType::INTEGER);
        DB::setQuery($query);

        if (!$names = DB::loadAssoc()) {
            return '';
        }

        $suffix = '';

        if ($withNumber and !empty($names['subjectNo'])) {
            $suffix .= " ({$names['subjectNo']})";
        }

        if ($names['name']) {
            return $names['name'] . $suffix;
        }

        return $names['abbreviation'] . $suffix;
    }

    /**
     * @inheritDoc
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::resources() as $subject) {
            $options[] = HTML::option($subject['id'], $subject['name']);
        }

        return $options;
    }

    /**
     * @inheritDoc
     */
    public static function resources(): array
    {
        $poolID    = Input::getInt('poolID', self::NONE);
        $programID = Input::getInt('programID', self::NONE);
        $personID  = Input::getInt('personID', self::NONE);

        // Without a valid restriction there are too many results.
        if ($poolID === self::NONE and $programID === self::NONE and $personID === self::NONE) {
            return [];
        }

        $query = DB::getQuery();
        $tag   = Application::getTag();

        $subjectID = DB::qn('s.id');
        $aliased   = [DB::qn("s.fullName_$tag", 'name')];
        $these     = ["DISTINCT $subjectID"];
        $those     = DB::qn(['p.surname, p.forename, p.title, p.username, s.code, s.creditPoints']);

        $query->select(array_merge($these, $those, $aliased))->from(DB::qn('#__organizer_subjects', 's'))
            ->order(DB::qn('name'))->group($subjectID);

        if ($ranges = self::filterRanges()) {
            $query->innerJoin(DB::qn('#__organizer_curricula', 'c'), DB::qc('c.subjectID', 's.id'));

            $count   = 1;
            $left    = DB::qn('c.lft');
            $right   = DB::qn('c.rgt');
            $wherray = [];

            foreach ($ranges as $range) {
                $bLeft     = ":left$count";
                $bRight    = ":right$count";
                $wherray[] = "( $left >= $bLeft AND $right <= $bRight )";
                $query->bind($bLeft, $range['lft'], ParameterType::INTEGER)->bind($bRight, $range['rgt'], ParameterType::INTEGER);
                $count++;
            }

            $query->where('(' . implode(' OR ', $wherray) . ')');
        }

        $condition = DB::qc('sp.subjectID', 's.id');
        $table     = DB::qn('#__organizer_subject_persons', 'sp');
        if ($personID) {
            $query->innerJoin($table, $condition)
                ->where("sp.personID = :personID")->bind(':personID', $personID, ParameterType::INTEGER);
        }
        else {
            $coordinates = Persons::COORDINATES;
            $query->leftJoin($table, $condition)
                ->where(DB::qc('sp.role', ':roleID'))->bind(':roleID', $coordinates, ParameterType::INTEGER);
        }

        $query->leftJoin(DB::qn('#__organizer_persons', 'p'), DB::qc('p.id', 'sp.personID'));

        DB::setQuery($query);

        return DB::loadAssocList('id');
    }

    /**
     * Checks whether the pool is subordinate to the selected program
     *
     * @param   array  $poolBoundaries     the pool's left and right values
     * @param   array  $programBoundaries  the program's left and right values
     *
     * @return bool  true if the pool is subordinate to the program,
     *                   otherwise false
     */
    public static function included(array $poolBoundaries, array $programBoundaries): bool
    {
        $first = $poolBoundaries[0];
        $last  = end($poolBoundaries);

        $leftValid  = $first['lft'] > $programBoundaries[0]['lft'];
        $rightValid = $last['rgt'] < $programBoundaries[0]['rgt'];
        if ($leftValid and $rightValid) {
            return true;
        }

        return false;
    }

    /**
     * Gets an array modeling the attributes of the resource.
     *
     * @param   int  $subjectID  the id of the subject
     *
     * @return array
     */
    public static function load(int $subjectID): array
    {
        $table = new Tables\Subjects();

        if (!$table->load($subjectID)) {
            return [];
        }

        $eventID         = Subjects::eventID($subjectID);
        $fieldID         = $table->fieldID ?: 0;
        $organizationIDs = self::organizationIDs($table->id);
        $organizationID  = $organizationIDs ? $organizationIDs[0] : 0;
        $tag             = Application::getTag();

        return [
            'abbreviation' => $table->{"abbreviation_$tag"},
            'bgColor'      => Fields::getColor($fieldID, $organizationID),
            'creditPoints' => $table->creditPoints,
            'eventID'      => $eventID,
            'field'        => $fieldID ? Fields::getName($fieldID) : '',
            'fieldID'      => $table->fieldID,
            'id'           => $table->id,
            'moduleNo'     => $table->code,
            'name'         => $table->{"fullName_$tag"}
        ];
    }

    /**
     * Retrieves the persons associated with a given subject and their respective roles for it.
     *
     * @param   int  $subjectID  the id of the subject with which the persons must be associated
     * @param   int  $roleID     the role to be filtered against default none
     *
     * @return array the persons associated with the subject, empty if none were found.
     */
    public static function persons(int $subjectID, int $roleID = 0): array
    {
        $query = DB::getQuery();
        $query->select(DB::qn(['p.id', 'p.surname', 'p.forename', 'p.title', 'sp.role']))
            ->from(DB::qn('#__organizer_persons', 'p'))
            ->innerJoin(DB::qn('#__organizer_subject_persons', 'sp'), DB::qc('sp.personID', 'p.id'))
            ->where(DB::qn('sp.subjectID') . ' = :subjectID')->bind(':subjectID', $subjectID, ParameterType::INTEGER);

        if ($roleID) {
            $query->where(DB::qn('sp.role') . ' = :roleID')->bind(':roleID', $roleID, ParameterType::INTEGER);
        }

        DB::setQuery($query);

        if (!$results = DB::loadAssocList()) {
            return [];
        }

        $persons = [];
        foreach ($results as $person) {
            $forename = empty($person['forename']) ? '' : $person['forename'];
            $fullName = $person['surname'];
            $fullName .= empty($forename) ? '' : ", {$person['forename']}";
            if (empty($persons[$person['id']])) {
                $person['forename'] = $forename;
                $person['title']    = empty($person['title']) ? '' : $person['title'];
                $person['role']     = [$person['role'] => $person['role']];
                $persons[$fullName] = $person;
                continue;
            }

            $persons[$person['id']]['role'] = [$person['role'] => $person['role']];
        }

        Persons::sortByRole($persons);
        Persons::sortByName($persons);

        return $persons;
    }

    /**
     * Looks up the names of the pools associated with the subject
     *
     * @param   int  $subjectID  the id of the (plan) subject
     *
     * @return array|array[] the associated program names
     */
    public static function pools(int $subjectID): array
    {
        return Pools::rows(self::rows($subjectID));
    }

    /**
     * Retrieves the ids of subjects registered as prerequisites for a given subject
     *
     * @param   int  $subjectID  the id of the subject
     *
     * @return int[] the associated prerequisites
     */
    public static function postrequisites(int $subjectID): array
    {
        return self::requisites($subjectID, 'post');
    }

    /**
     * Retrieves the ids of subjects registered as prerequisites for a given subject
     *
     * @param   int  $subjectID  the id of the subject
     *
     * @return int[] the associated prerequisites
     */
    public static function prerequisites(int $subjectID): array
    {
        return self::requisites($subjectID, 'pre');
    }

    /**
     * @inheritDoc
     */
    public static function rows(array|int $identifiers): array
    {
        // Signature demands allowing an array to this point.
        if (!$identifiers or is_array($identifiers)) {
            return [];
        }

        $query = DB::getQuery();
        $query->select('DISTINCT *')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('subjectID') . ' = :subjectID')->bind(':subjectID', $identifiers, ParameterType::INTEGER)
            ->order(DB::qn('lft'));
        DB::setQuery($query);

        return DB::loadAssocList();
    }

    /**
     * Retrieves the ids of subjects registered as prerequisites for a given subject
     *
     * @param   int     $subjectID  the id of the subject
     * @param   string  $direction  pre|post the direction of the subject dependency
     *
     * @return int[] the associated prerequisites
     */
    private static function requisites(int $subjectID, string $direction): array
    {
        if ($direction === 'pre') {
            $fromColumn = 'subjectID';
            $toColumn   = 'prerequisiteID';
        }
        else {
            $fromColumn = 'prerequisiteID';
            $toColumn   = 'subjectID';
        }

        $query = DB::getQuery();
        $query->select('DISTINCT ' . DB::qn('target.subjectID'))
            ->from(DB::qn('#__organizer_curricula', 'target'))
            ->innerJoin(DB::qn('#__organizer_prerequisites', 'p'), DB::qc("p.$toColumn", 'target.id'))
            ->innerJoin(DB::qn('#__organizer_curricula', 'source'), DB::qc('source.id', "p.$fromColumn"))
            ->where(DB::qn('source.subjectID') . ' = :subjectID')->bind(':subjectID', $subjectID, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::loadIntColumn();
    }

    /**
     * Check if the user is one of the subject's teachers.
     *
     * @param   int  $subjectID  the optional id of the subject
     * @param   int  $personID   the optional id of the person entry
     *
     * @return bool true if the user a teacher for the subject, otherwise false
     */
    public static function teaches(int $subjectID = 0, int $personID = 0): bool
    {
        if (!$personID = $personID ?: Persons::getIDByUserID(User::id())) {
            return false;
        }

        $teaches = Persons::TEACHES;
        $query   = DB::getQuery();
        $query->select('COUNT(*)')
            ->from(DB::qn('#__organizer_subject_persons'))
            ->where(DB::qn('personID') . ' = :personID')->bind(':personID', $personID, ParameterType::INTEGER)
            ->where(DB::qn('role') . ' = :roleID')->bind(':roleID', $teaches, ParameterType::INTEGER);

        if ($subjectID) {
            $query->where(DB::qn('subjectID') . ' = :subjectID')->bind(':subjectID', $subjectID, ParameterType::INTEGER);
        }

        DB::setQuery($query);

        return DB::loadBool();
    }
}
