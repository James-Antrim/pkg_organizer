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

use Joomla\Database\DatabaseQuery;
use THM\Organizer\Adapters\{Database as DB, Text};
use Joomla\Database\ParameterType;

/**
 * Class contains methods and method stubs useful in the context of nested curriculum resources.
 */
abstract class Curricula extends Associated implements Selectable
{
    // Both are string values on the line the latter must be cast
    protected const ALL = 0, NONE = -1;

    /**
     * Recursively builds the curriculum hierarchy inclusive data for resources subordinate to a given range.
     *
     * @param   array  $curriculum  the range used as the start point
     *
     * @return void
     */
    public static function curriculum(array &$curriculum): void
    {
        $invalidRange = (empty($curriculum['lft']) or empty($curriculum['rgt']) or $curriculum['subjectID']);
        if ($invalidRange) {
            $curriculum['curriculum'] = [];

            return;
        }

        $query = DB::getQuery();
        $query->select('*')
            ->from('#__organizer_curricula')
            ->where("lft > {$curriculum['lft']}")
            ->where("rgt < {$curriculum['rgt']}")
            ->where("level = {$curriculum['level']} + 1")
            ->order('ordering');

        // Only pools should be direct subordinates of programs
        if ($curriculum['programID']) {
            $query->where("poolID IS NOT NULL");
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
     * Gets the ids of resources for which the user has documentation access.
     *
     * @return int[]
     */
    public static function documentable(string $resource): array
    {
        if (!$organizationIDs = Can::documentTheseOrganizations()) {
            return [];
        }

        $organizationID = DB::qn('organizationID');
        $resourceID     = DB::qn("{$resource}ID");

        $query = DB::getQuery();
        $query->select("DISTINCT $resourceID")
            ->from('#__organizer_associations')
            ->where("$resourceID IS NOT NULL");

        if (!Can::administrate()) {
            $query->whereIn($organizationID, $organizationIDs);
        }

        DB::setQuery($query);

        return DB::loadIntColumn();
    }

    /**
     * Extracts the parent ids from an array of ranges.
     *
     * @param   array  $ranges  the ranges to filter
     *
     * @return int[]
     */
    private static function extractParentIDs(array $ranges): array
    {
        $ids = [];
        foreach ($ranges as $range) {
            $ids[] = $range['parentID'];
        }

        return $ids;
    }

    /**
     * Adds clauses to an array to find subordinate resources in an error state disassociated from a superordinate
     * resource type.
     *
     * @param   DatabaseQuery  $query   the query to modify
     * @param   array          $ranges  the ranges of the possible superordinate resources
     * @param   string         $alias   the alias to use in the query
     *
     * @return void
     */
    protected static function filterDisassociated(DatabaseQuery $query, array $ranges, string $alias): void
    {
        $aLeft  = DB::qn("$alias.lft");
        $aRight = DB::qn("$alias.rgt");
        $count  = 1;
        $erray  = [];
        $id     = DB::qn("$alias.id");

        foreach ($ranges as $range) {
            $bLeft  = ":left$count";
            $bRight = ":right$count";

            $erray[] = "( $aLeft NOT BETWEEN $bLeft AND $bRight' )";
            $erray[] = "( $aRight NOT BETWEEN $bLeft AND $bRight )";

            $query->bind($bLeft, $range['lft'], ParameterType::INTEGER)
                ->bind($bRight, $range['rgt'], ParameterType::INTEGER);
            $count++;
        }

        $errorClauses = implode(' AND ', $erray);
        $query->where("( ($errorClauses) OR $id IS NULL ) ");
    }

    /**
     * Filters the curricula ids from an array of ranges.
     *
     * @param   array  $ranges  the ranges to filter
     *
     * @return int[] the curricular ids contained in the ranges
     */
    public static function filterIDs(array $ranges): array
    {
        $ids = [];
        foreach ($ranges as $range) {
            $ids[] = empty($range['id']) ? $range['curriculumID'] : $range['id'];
        }

        return $ids;
    }

    /**
     * Adds a program filter clause to the given query.
     *
     * @param   DatabaseQuery  $query   the query to modify
     * @param   int            $poolID  the id of the pool to filter for
     * @param   string         $alias   the alias of the table referenced in the join
     *
     * @return void
     */
    public static function filterPool(DatabaseQuery $query, int $poolID, string $alias): void
    {
        if (!$poolID or !$ranges = Pools::ranges($poolID)) {
            return;
        }

        // Program context is a precondition for none, so this filters for subjects directly associated with a program.
        if ($poolID === self::NONE) {
            $query->innerJoin(DB::qn('#__organizer_curricula', 'parent'), DB::qc('parent.ID', 'prc.parentID'))
                ->where(DB::qn('parent.programID') . ' IS NOT NULL');

            return;
        }

        $range = array_pop($ranges);
        $query->innerJoin(DB::qn('#__organizer_curricula', 'poc'), DB::qc('poc.subjectID', "$alias.id"))
            ->where([DB::qn('poc.lft') . ' > :left', DB::qn('poc.rgt') . ' < :right'])
            ->bind(':left', $range['lft'], ParameterType::INTEGER)
            ->bind(':right', $range['rgt'], ParameterType::INTEGER);
    }

    /**
     * Adds a program filter clause to the given query.
     *
     * @param   DatabaseQuery  $query      the query to modify
     * @param   int            $programID  the id of the program to filter for
     * @param   string         $context    the resource context from which this function was called
     * @param   string         $alias      the alias of the table referenced in the join
     *
     * @return void
     */
    public static function filterProgram(DatabaseQuery $query, int $programID, string $context, string $alias): void
    {
        if (!$programID or !$ranges = Programs::ranges($programID)) {
            return;
        }

        $condition = DB::qc("prc.{$context}ID", "$alias.id");
        $table     = DB::qn('#__organizer_curricula', 'prc');
        $range     = array_pop($ranges);

        if ($programID === self::NONE) {
            $query->leftJoin($table, $condition)->where(DB::qn("prc.{$context}ID") . ' IS NULL');

            return;
        }

        $query->innerJoin($table, $condition)
            ->where(DB::qc('prc.lft', ':left', '>'))->bind(':left', $range['lft'], ParameterType::INTEGER)
            ->where(DB::qc('prc.rgt', ':right', '<'))->bind(':right', $range['rgt'], ParameterType::INTEGER);
    }

    /**
     * Adds range restrictions for subordinate resources.
     *
     * @param   DatabaseQuery  $query      the query to modify
     * @param   array          $ranges     the ranges of subordinate resources
     * @param   int            $subjectID  the id of a specific subject resource to find in context
     *
     * @return void
     */
    private static function filterSubOrdinate(DatabaseQuery $query, array $ranges, int $subjectID = 0): void
    {
        $count   = 1;
        $left    = DB::qn('lft');
        $right   = DB::qn('rgt');
        $wherray = [];

        foreach ($ranges as $range) {
            $bLeft     = ":left$count";
            $bRight    = ":right$count";
            $wherray[] = "( $left > $bLeft AND $right < $bRight )";
            $query->bind($bLeft, $range['lft'], ParameterType::INTEGER)
                ->bind($bRight, $range['rgt'], ParameterType::INTEGER);
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
     * Adds range restrictions for subordinate resources.
     *
     * @param   DatabaseQuery  $query   the query to modify
     * @param   array          $ranges  the ranges of subordinate resources
     *
     * @return void
     */
    protected static function filterSuperOrdinate(DatabaseQuery $query, array $ranges): void
    {
        $count   = 1;
        $left    = DB::qn('lft');
        $right   = DB::qn('rgt');
        $wherray = [];

        foreach ($ranges as $range) {
            $bLeft     = ":left$count";
            $bRight    = ":right$count";
            $wherray[] = "( $left < $bLeft AND $right > $bRight)";
            $query->bind($bLeft, $range['lft'], ParameterType::INTEGER)
                ->bind($bRight, $range['rgt'], ParameterType::INTEGER);
            $count++;
        }

        $query->where('(' . implode(' OR ', $wherray) . ')');
    }

    /**
     * Retrieves all curriculum ranges subordinate to a program
     *
     * @param   array  $ranges  the ranges of superordinate programs
     *
     * @return array[]  an array containing all ranges subordinate to the ranges specified
     */
    private static function mappable(array $ranges): array
    {
        $query = DB::getQuery();
        $query->select('*')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('lft') . ' >= :left')
            ->where(DB::qn('rgt') . ' <= :right')
            ->where('subjectID IS NULL')
            ->order('lft ASC');

        $items = [];
        foreach ($ranges as $range) {
            $query->bind(':left', $range['lft'], ParameterType::INTEGER)->bind(':right', $range['rgt'], ParameterType::INTEGER);
            DB::setQuery($query);

            if (!$results = DB::loadAssocList()) {
                continue;
            }

            $items = array_merge($items, $results);
        }

        return $items;
    }

    /**
     * Retrieves the range for a given id.
     *
     * @param   int  $rangeID  the id of the range requested
     *
     * @return array  curriculum range
     */
    public static function range(int $rangeID): array
    {
        $query = DB::getQuery();
        $query->select('*')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qc('id', ':rangeID'))
            ->bind(':rangeID', $rangeID, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::loadAssoc();
    }

    /**
     * Gets the mapped curricula ranges for the given resource
     *
     * @param   int  $resourceID  the resource ID
     *
     * @return int[] the resource ranges
     */
    public static function rangeIDs(int $resourceID): array
    {
        /** @var Pools|Programs|Subjects $self */
        $self = get_called_class();

        return self::filterIDs($self::ranges($resourceID));
    }

    /**
     * Gets the mapped curricula ranges for the given resource
     *
     * @param   array|int  $identifiers  ranges of subordinate resources | resource id
     *
     * @return array[] the resource ranges
     */
    public static function ranges(array|int $identifiers): array
    {
        /** @var Pools|Programs|Subjects $self */
        $self = get_called_class();

        return $self::ranges($identifiers);
    }

    /**
     * Retrieves a list of options for choosing superordinate entries in the curriculum hierarchy.
     *
     * @param   int     $resourceID     the id of the resource for which the form is being displayed
     * @param   string  $type           the type of the resource
     * @param   array   $programRanges  the ranges for programs selected in the form, or already mapped
     *
     * @return string[] the superordinate resource options
     */
    public static function superOptions(int $resourceID, string $type, array $programRanges): array
    {
        $options = ['<option value="-1">' . Text::_('NONE') . '</option>'];

        if (!$programRanges or !$type) {
            return $options;
        }

        $mappable = self::mappable($programRanges);

        // The programs have no subordinate resources and subjects cannot be directly subordinated to programs
        if (count($mappable) === count($programRanges) and $type == 'subject') {
            return $options;
        }

        $selected = [];

        if ($resourceID) {
            if ($type === 'pool') {
                $selected = Pools::ranges($resourceID);

                foreach ($mappable as $mIndex => $mRange) {
                    foreach ($selected as $sRange) {
                        if ($mRange['lft'] >= $sRange ['lft'] and $mRange['rgt'] <= $sRange ['rgt']) {
                            unset($mappable[$mIndex]);
                        }
                    }
                }

            }
            else {
                $selected = Subjects::ranges($resourceID);
            }
        }

        $parentIDs = self::extractParentIDs($selected);

        foreach ($mappable as $range) {

            if (!empty($range['poolID'])) {
                $options[] = Pools::option($range, $parentIDs);
            }
            else {
                $options[] = Programs::option($range, $parentIDs, $type);
            }
        }

        return $options;
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
     * Looks up the names of the programs associated with the resource. Overwritten by the programs helper to prevent
     * endless regression.
     *
     * @param   array|int  $identifiers  ranges of subordinate resources | resource id
     *
     * @return array[] the associated programs
     */
    public static function programs(array|int $identifiers): array
    {
        $resource = get_called_class();

        /** @noinspection PhpUndefinedMethodInspection */
        return Programs::ranges($resource::ranges($identifiers));
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
            ->from('#__organizer_curricula')
            ->order('lft');

        $resource = get_called_class();
        /** @noinspection PhpUndefinedMethodInspection */
        self::filterSubOrdinate($query, $resource::getRanges($resourceID), $subjectID);

        DB::setQuery($query);

        return DB::loadAssocList();
    }
}