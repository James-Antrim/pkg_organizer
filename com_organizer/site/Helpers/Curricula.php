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

use Joomla\Database\{DatabaseQuery, ParameterType};
use THM\Organizer\Adapters\{Database as DB, Text};
use THM\Organizer\Controllers\Subject;

/**
 * Class contains methods and method stubs useful in the context of nested curriculum resources.
 */
abstract class Curricula extends Associated implements Selectable
{
    /**
     * Gets all curriculum rows for resources mapped to a program, unfiltered by type, including the program itself.
     *
     * @param   array  $rows  the rows of superordinate programs
     *
     * @return array[]
     */
    private static function allRows(array $rows): array
    {
        $query = DB::getQuery();
        $query->select('*')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('lft') . ' >= :left')
            ->where(DB::qn('rgt') . ' <= :right')
            ->where(DB::qn('subjectID') . 'IS NULL')
            ->order(DB::qn('lft'));

        $items = [];
        foreach ($rows as $row) {
            $query->bind(':left', $row['lft'], ParameterType::INTEGER)
                ->bind(':right', $row['rgt'], ParameterType::INTEGER);
            DB::setQuery($query);

            if (!$results = DB::loadAssocList()) {
                continue;
            }

            $items = array_merge($items, $results);
        }

        return $items;
    }

    /**
     * Recursively builds the curriculum hierarchy inclusive data for resources subordinate to a given rowe.
     *
     * @param   array  $curriculum  the row used as the start point
     *
     * @return void
     */
    public static function curriculum(array &$curriculum): void
    {
        if (empty($curriculum['lft']) or empty($curriculum['rgt']) or $curriculum['subjectID']) {
            $curriculum['curriculum'] = [];

            return;
        }

        $nextLevel = $curriculum['level'] + 1;
        $query     = DB::getQuery();
        $query->select('*')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('lft') . ' > :left')->bind(':left', $curriculum['lft'], ParameterType::INTEGER)
            ->where(DB::qn('rgt') . ' < :right')->bind(':right', $curriculum['rgt'], ParameterType::INTEGER)
            ->where(DB::qn('level') . ' = :level')->bind(':right', $nextLevel, ParameterType::INTEGER)
            ->order(DB::qn('ordering'));

        // Only pools should be direct subordinates of programs
        if ($curriculum['programID']) {
            $query->where(DB::qn('poolID') . ' IS NOT NULL');
        }

        DB::setQuery($query);

        if (!$subOrdinates = DB::loadAssocList('id')) {
            $curriculum['curriculum'] = [];

            return;
        }

        // Fill data for subordinate resources
        foreach ($subOrdinates as &$subOrdinate) {
            $resourceData = $subOrdinate['poolID'] ?
                Pools::load($subOrdinate['poolID']) : Subjects::load($subOrdinate['subjectID']);

            // Avoid conflicts between the resource's actual id and the curricula table id
            unset($resourceData['id']);

            $subOrdinate = array_merge($subOrdinate, $resourceData);
            if ($subOrdinate['poolID']) {
                self::curriculum($subOrdinate);
            }
        }

        $curriculum['curriculum'] = $subOrdinates;
    }

    /**
     * Extracts the curriculum ids from an array of arrays. Divergent handling comes from use by various subject controller
     * functions using the key curriculumID instead of id.
     *
     * @param   array  $arrays  the arrays to filter
     *
     * @return int[]
     * @see Subject
     */
    public static function curriculumIDs(array $arrays): array
    {
        $ids = [];
        foreach ($arrays as $array) {
            $ids[] = empty($array['id']) ? $array['curriculumID'] : $array['id'];
        }

        return $ids;
    }

    /**
     * Gets the ids of resources for which the user has documentation access.
     *
     * @param   string  $column  the name of the column referencing the specific resource
     *
     * @return array
     */
    public static function documentableIDs(string $column): array
    {
        if (!$organizationIDs = Can::documentTheseOrganizations()) {
            return [];
        }

        $organizationID = DB::qn('organizationID');
        $column         = DB::qn($column);

        $query = DB::getQuery();
        $query->select("DISTINCT $column")
            ->from(DB::qn('#__organizer_associations'))
            ->where("$column IS NOT NULL")
            ->whereIn($organizationID, $organizationIDs);

        DB::setQuery($query);

        return DB::loadIntColumn();
    }

    /**
     * Adds pool filter clauses to the given query.
     *
     * @param   DatabaseQuery  $query   the query to modify
     * @param   int            $poolID  the id of the pool to filter for
     * @param   string         $alias   the alias of the table referenced in the join
     *
     * @return void
     */
    public static function filterPool(DatabaseQuery $query, int $poolID, string $alias): void
    {
        if (!$poolID or !$rows = Pools::rows($poolID)) {
            return;
        }

        // Subjects directly subordinated to a program.
        if ($poolID === self::NONE) {
            $query->innerJoin(DB::qn('#__organizer_curricula', 'parent'), DB::qc('parent.ID', 'prc.parentID'))
                ->where(DB::qn('parent.programID') . ' IS NOT NULL');

            return;
        }

        $row = array_pop($rows);
        $query->innerJoin(DB::qn('#__organizer_curricula', 'poc'), DB::qc('poc.subjectID', "$alias.id"))
            ->where([DB::qn('poc.lft') . ' > :left', DB::qn('poc.rgt') . ' < :right'])
            ->bind(':left', $row['lft'], ParameterType::INTEGER)
            ->bind(':right', $row['rgt'], ParameterType::INTEGER);
    }

    /**
     * Adds program filter clauses to the given query.
     *
     * @param   DatabaseQuery  $query      the query to modify
     * @param   int            $programID  the id of the program to filter for
     * @param   string         $column     the name of the column referencing the specific resource
     * @param   string         $alias      the alias of the table referenced in the join
     *
     * @return void
     */
    public static function filterProgram(DatabaseQuery $query, int $programID, string $column, string $alias): void
    {
        if (!$programID or !$rows = Programs::rows($programID)) {
            return;
        }

        $condition = DB::qc("prc.$column", "$alias.id");
        $table     = DB::qn('#__organizer_curricula', 'prc');
        $row       = array_pop($rows);

        if ($programID === self::NONE) {
            $query->leftJoin($table, $condition)->where(DB::qn("prc.$column") . ' IS NULL');

            return;
        }

        $query->innerJoin($table, $condition)
            ->where(DB::qc('prc.lft', ':left', '>'))->bind(':left', $row['lft'], ParameterType::INTEGER)
            ->where(DB::qc('prc.rgt', ':right', '<'))->bind(':right', $row['rgt'], ParameterType::INTEGER);
    }

    /**
     * Adds subject filter clauses to the given query.
     *
     * @param   DatabaseQuery  $query      the query to modify
     * @param   array          $rows       the rows of subordinate resources
     * @param   int            $subjectID  the id of a specific subject resource to find in context
     *
     * @return void
     */
    private static function filterSubject(DatabaseQuery $query, array $rows, int $subjectID = 0): void
    {
        $count   = 1;
        $left    = DB::qn('lft');
        $right   = DB::qn('rgt');
        $wherray = [];

        foreach ($rows as $row) {
            $bLeft     = ":left$count";
            $bRight    = ":right$count";
            $wherray[] = "( $left > $bLeft AND $right < $bRight )";
            $query->bind($bLeft, $row['lft'], ParameterType::INTEGER)
                ->bind($bRight, $row['rgt'], ParameterType::INTEGER);
            $count++;
        }

        if ($wherray) {
            $query->where('( ' . implode(' OR ', $wherray) . ' )');
        }

        $column = DB::qn('subjectID');
        if ($subjectID) {
            $query->where("$column = :subjectID")->bind(':subjectID', $subjectID, ParameterType::INTEGER);
        }
        else {
            $query->where("$column IS NOT NULL");
        }
    }

    /**
     * Adds superordinate resource restriction clauses to the given query.
     *
     * @param   DatabaseQuery  $query  the query to modify
     * @param   array          $rows   the rows of subordinate resources
     *
     * @return void
     */
    protected static function filterSuperOrdinate(DatabaseQuery $query, array $rows): void
    {
        $count   = 1;
        $left    = DB::qn('lft');
        $right   = DB::qn('rgt');
        $wherray = [];

        foreach ($rows as $row) {
            $bLeft     = ":left$count";
            $bRight    = ":right$count";
            $wherray[] = "( $left < $bLeft AND $right > $bRight)";
            $query->bind($bLeft, $row['lft'], ParameterType::INTEGER)
                ->bind($bRight, $row['rgt'], ParameterType::INTEGER);
            $count++;
        }

        $query->where('(' . implode(' OR ', $wherray) . ')');
    }

    /**
     * Extracts the parent ids from an array of rows.
     *
     * @param   array  $rows  the rows to filter
     *
     * @return int[]
     */
    private static function parentIDs(array $rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = $row['parentID'];
        }

        return $ids;
    }

    /**
     * Retrieves a string value representing the degree programs to which the resource is associated.
     *
     * @param   int  $resourceID  the id of the resource
     *
     * @return string  string representing the associated program(s)
     */
    public static function programName(int $resourceID): string
    {
        if (!$programs = self::programs($resourceID)) {
            return Text::_('NO_PROGRAMS');
        }

        if (count($programs) === 1) {
            return Programs::getName($programs[0]['programID']);
        }
        else {
            return Text::_('MULTIPLE_PROGRAMS');
        }
    }

    /**
     * Looks up the names of the programs associated with the resource. Overwritten by the programs helper to prevent endless
     * regression.
     *
     * @param   array|int  $identifiers  rows of subordinate resources | resource id
     *
     * @return array[] the associated programs
     */
    public static function programs(array|int $identifiers): array
    {
        /** @var Pools|Programs|Subjects $resource */
        $resource = get_called_class();

        return Programs::rows($resource::rows($identifiers));
    }

    /**
     * Gets the curricula row mapped to associative array for a given id.
     *
     * @param   int  $rowID  the id of the row requested
     *
     * @return array
     */
    public static function row(int $rowID): array
    {
        $query = DB::getQuery();
        $query->select('*')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qc('id', ':rowID'))
            ->bind(':rowID', $rowID, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::loadAssoc();
    }

    /**
     * Gets the curricula row ids specific to the resource of the calling class.
     *
     * @param   int  $resourceID  the resource ID
     *
     * @return int[]
     */
    public static function rowIDs(int $resourceID): array
    {
        /** @var Pools|Programs|Subjects $self */
        $self = get_called_class();

        return self::curriculumIDs($self::rows($resourceID));
    }

    /**
     * Gets the curricula rows mapped to associative arrays specific to the resource of the calling class.
     *
     * @param   array|int  $identifiers  rows of subordinate resources | resource id
     *
     * @return array[]
     */
    public static function rows(array|int $identifiers): array
    {
        /** @var Pools|Programs|Subjects $self */
        $self = get_called_class();

        return $self::rows($identifiers);
    }

    /**
     * Retrieves a list of options for choosing superordinate entries in the curriculum hierarchy.
     *
     * @param   int     $resourceID   the id of the resource for which the form is being displayed
     * @param   string  $type         the type of the resource
     * @param   array   $programRows  the rows for programs selected in the form, or already mapped
     *
     * @return string[] the superordinate resource options
     */
    public static function superOptions(int $resourceID, string $type, array $programRows): array
    {
        $options = ['<option value="-1">' . Text::_('NONE') . '</option>'];

        if (!$programRows or !$type) {
            return $options;
        }

        $rows = self::allRows($programRows);

        // The programs have no subordinate resources and subjects cannot be directly subordinated to programs
        if (count($rows) === count($programRows) and $type == 'subject') {
            return $options;
        }

        $selected = [];

        if ($resourceID) {
            if ($type === 'pool') {
                $selected = Pools::rows($resourceID);

                foreach ($rows as $key => $row) {
                    foreach ($selected as $sRange) {
                        if ($row['lft'] >= $sRange ['lft'] and $row['rgt'] <= $sRange ['rgt']) {
                            unset($rows[$key]);
                        }
                    }
                }

            }
            else {
                $selected = Subjects::rows($resourceID);
            }
        }

        $parentIDs = self::parentIDs($selected);

        foreach ($rows as $row) {

            if (!empty($row['poolID'])) {
                $options[] = Pools::option($row, $parentIDs);
            }
            else {
                $options[] = Programs::option($row, $parentIDs, $type);
            }
        }

        return $options;
    }

    /**
     * Finds the subject entries subordinate to a particular resource.
     *
     * @param   int  $resourceID  the id of the resource
     * @param   int  $subjectID   the id of a specific subject resource to find in context
     *
     * @return array[] the associated programs
     */
    public static function subjects(int $resourceID, int $subjectID = 0): array
    {
        $query = DB::getQuery();
        $query->select('DISTINCT *')
            ->from(DB::qn('#__organizer_curricula'))
            ->order(DB::qn('lft'));

        /** @var Pools|Programs $resource */
        $resource = get_called_class();
        self::filterSubject($query, $resource::rows($resourceID), $subjectID);

        DB::setQuery($query);

        return DB::loadAssocList();
    }
}