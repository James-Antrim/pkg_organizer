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
use stdClass;
use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input, Text};
use THM\Organizer\Controllers\Subject;
use THM\Organizer\Tables\Curricula as Table;

/**
 * Class contains methods and method stubs useful in the context of nested curriculum resources.
 */
abstract class Curricula extends Associated implements Documentable, Selectable
{
    /**
     * Adds a curriculum range to a parent curriculum range
     *
     * @param array &$range an array containing data about a curriculum item and potentially its children
     *
     * @return int the id of the curriculum row on success, otherwise 0
     */
    public static function addRange(array &$range): int
    {
        $curricula = new Table();

        if (empty($range['programID'])) {
            // Subordinates must have a parent
            if (empty($range['parentID']) or !$parent = self::row($range['parentID'])) {
                return 0;
            }

            // No resource
            if (empty($range['poolID']) and empty($range['subjectID'])) {
                return 0;
            }

            $conditions = ['parentID' => $range['parentID']];

            if (empty($range['subjectID'])) {
                $conditions['poolID'] = $range['poolID'];
            }
            else {
                $conditions['subjectID'] = $range['subjectID'];
            }
        }
        else {
            $conditions = ['programID' => $range['programID']];
            $parent     = null;
        }

        if ($curricula->load($conditions)) {
            $curricula->ordering = $range['ordering'];
            if (!$curricula->store()) {
                return 0;
            }
        }
        else {
            if (!empty($range['programID'])) {
                $range['parentID'] = null;
            }

            $range['lft'] = self::left($range['parentID'], $range['ordering']);

            if (!$range['lft'] or !self::shiftRight($range['lft'])) {
                return 0;
            }

            $range['level'] = $parent ? $parent['level'] + 1 : 0;
            $range['rgt']   = $range['lft'] + 1;

            if (!$curricula->save($range)) {
                return 0;
            }
        }

        if (!empty($range['curriculum'])) {
            $subRangeIDs = [];

            foreach ($range['curriculum'] as $subOrdinate) {
                $subOrdinate['parentID'] = $curricula->id;

                if (!$subRangeID = self::addRange($subOrdinate)) {
                    return 0;
                }

                $subRangeIDs[$subRangeID] = $subRangeID;
            }

            if ($subRangeIDs) {
                $query = DB::query();
                $query->select(DB::qn('id'))
                    ->from(DB::qn('#__organizer_curricula'))
                    ->whereNotIn(DB::qn('id'), $subRangeIDs)
                    ->where(DB::qn('parentID') . ' = :curriculaID')
                    ->bind(':curriculaID', $curricula->id, ParameterType::INTEGER);
                DB::set($query);

                if ($zombieIDs = DB::integers()) {
                    foreach ($zombieIDs as $zombieID) {
                        self::deleteRange($zombieID);
                    }
                }
            }
        }

        return $curricula->id;
    }

    /**
     * Adds ranges for the resource to the given superordinate ranges.
     *
     * @param array $data           the resource data from the form
     * @param array $superOrdinates the valid superordinate ranges to which to create/validate ranges within
     *
     * @return bool
     */
    private static function addSubordinate(array $data, array $superOrdinates): bool
    {
        switch (Application::uqClass(get_called_class())) {
            case 'Pools':
                $range = [
                    'poolID'     => $data['id'],
                    'curriculum' => Input::task() !== 'Pool.save2copy' ? self::subordinates() : []
                ];
                break;
            case 'Subjects':
                $range = [
                    'subjectID'  => $data['id'],
                    'curriculum' => []
                ];
                break;
            default:
                return false;
        }

        $ranges = self::rows($data['id']);

        foreach ($superOrdinates as $super) {
            $range['parentID'] = $super['id'];

            foreach ($ranges as $index => $existing) {
                // There is an existing direct subordinate relationship
                if ($existing['parentID'] === $super['id']) {
                    // Prevent further iteration of an established relationship
                    unset($ranges[$index]);

                    // Update subordinate curricula entries as necessary
                    foreach ($range['curriculum'] as $subOrdinate) {
                        $subOrdinate['parentID'] = $existing['id'];

                        self::addRange($subOrdinate);
                    }

                    continue 2;
                }
            }

            $range['ordering'] = self::ordering($super['id'], $data['id']);

            if (!self::addRange($range)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gets all curriculum rows for resources mapped to a program, unfiltered by type, including the program itself.
     *
     * @param array $rows the rows of superordinate programs
     *
     * @return array[]
     */
    private static function allRows(array $rows): array
    {
        $query = DB::query();
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
            DB::set($query);

            if (!$results = DB::arrays()) {
                continue;
            }

            $items = array_merge($items, $results);
        }

        return $items;
    }

    /**
     * Recursively builds the curriculum hierarchy inclusive data for resources subordinate to a given rowe.
     *
     * @param array $curriculum the row used as the start point
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
        $query     = DB::query();
        $query->select('*')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('lft') . ' > :left')->bind(':left', $curriculum['lft'], ParameterType::INTEGER)
            ->where(DB::qn('rgt') . ' < :right')->bind(':right', $curriculum['rgt'], ParameterType::INTEGER)
            ->where(DB::qn('level') . ' = :level')->bind(':level', $nextLevel, ParameterType::INTEGER)
            ->order(DB::qn('ordering'));

        // Only pools should be direct subordinates of programs
        if ($curriculum['programID']) {
            $query->where(DB::qn('poolID') . ' IS NOT NULL');
        }

        DB::set($query);

        if (!$subOrdinates = DB::arrays('id')) {
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
     * @param array $arrays the arrays to filter
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
     * Method to delete a single range from the curricula table
     *
     * @param int $rangeID the id value of the range to be deleted
     *
     * @return bool  true on success, otherwise false
     */
    protected static function deleteRange(int $rangeID): bool
    {
        if (!$range = self::row($rangeID)) {
            return false;
        }

        // Deletes the range
        $curricula = new Table();

        if (!$curricula->delete($rangeID)) {
            return false;
        }

        // Reduces the ordering of siblings with a greater ordering
        if (!empty($range['parentID']) and !self::shiftDown($range['parentID'], $range['ordering'])) {
            return false;
        }

        $width = $range['rgt'] - $range['lft'] + 1;

        return self::shiftLeft($range['lft'], $width);
    }

    /**
     * Deletes ranges of a specific curriculum resource.
     *
     * @param int $resourceID the id of the resource
     *
     * @return bool true on success, otherwise false
     */
    public static function deleteRanges(int $resourceID): bool
    {
        if ($rangeIDs = self::rowIDs($resourceID)) {
            foreach ($rangeIDs as $rangeID) {
                $success = self::deleteRange($rangeID);
                if (!$success) {
                    return false;
                }
            }
        }

        return true;
    }

    /** @inheritDoc */
    public static function documentable(int $resourceID): bool
    {
        if (Can::administrate()) {
            return true;
        }

        if (!$organizationIDs = Organizations::documentableIDs()) {
            return false;
        }
        // Document authorization has already been established, allow new
        elseif (!$resourceID) {
            return true;
        }

        return self::associated($organizationIDs, $resourceID);
    }

    /** @inheritDoc */
    public static function documentableIDs(): array
    {
        if (!$organizationIDs = Organizations::documentableIDs()) {
            return [];
        }

        return self::associatedIDs($organizationIDs);
    }

    /**
     * Adds pool filter clauses to the given query.
     *
     * @param DatabaseQuery $query     the query to modify
     * @param int           $poolID    the id of the pool to filter for
     * @param string        $alias     the alias of the table referenced in the join
     * @param int           $programID the optional programID
     *
     * @return void
     */
    public static function filterPool(DatabaseQuery $query, int $poolID, string $alias, int $programID = 0): void
    {
        if (!$poolID) {
            return;
        }

        // Subjects misconfigured directly to a program.
        if ($poolID === self::NONE) {

            if (!$programID) {
                $query->innerJoin(DB::qn('#__organizer_curricula', 'prc'), DB::qc("prc.subjectID", "$alias.id"));
            }

            $query->innerJoin(DB::qn('#__organizer_curricula', 'parent'), DB::qc('parent.ID', 'prc.parentID'))
                ->where(DB::qn('parent.programID') . ' IS NOT NULL');

            return;
        }

        if (!$rows = Pools::rows($poolID)) {
            return;
        }

        $row = array_pop($rows);
        $query->innerJoin(DB::qn('#__organizer_curricula', 'poc'), DB::qc('poc.subjectID', "$alias.id"))
            ->where(DB::qn('poc.lft') . '> :poLeft')->bind(':poLeft', $row['lft'], ParameterType::INTEGER)
            ->where(DB::qn('poc.rgt') . '< :poRight')->bind(':poRight', $row['rgt'], ParameterType::INTEGER);
    }

    /**
     * Adds program filter clauses to the given query.
     *
     * @param DatabaseQuery $query     the query to modify
     * @param int           $programID the id of the program to filter for
     * @param string        $column    the name of the column referencing the specific resource
     * @param string        $alias     the alias of the table referenced in the join
     *
     * @return void
     */
    public static function filterProgram(DatabaseQuery $query, int $programID, string $column, string $alias): void
    {
        if (!$programID) {
            return;
        }

        $condition = DB::qc("prc.$column", "$alias.id");
        $table     = DB::qn('#__organizer_curricula', 'prc');

        if ($programID === self::NONE) {
            $query->leftJoin($table, $condition)->where(DB::qn("prc.$column") . ' IS NULL');

            return;
        }

        if (!$rows = Programs::rows($programID)) {
            return;
        }

        $row = array_pop($rows);

        $query->innerJoin($table, $condition)
            ->where(DB::qn('prc.lft') . '> :prLeft')->bind(':prLeft', $row['lft'], ParameterType::INTEGER)
            ->where(DB::qn('prc.rgt') . '< :prRight')->bind(':prRight', $row['rgt'], ParameterType::INTEGER);
    }

    /**
     * Adds subject filter clauses to the given query.
     *
     * @param DatabaseQuery $query the query to modify
     * @param array         $rows  the rows of subordinate resources
     *
     * @return void
     */
    private static function filterSubjects(DatabaseQuery $query, array $rows): void
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

        $query->where(DB::qn('subjectID') . ' IS NOT NULL');
    }

    /**
     * Adds superordinate resource restriction clauses to the given query.
     *
     * @param DatabaseQuery $query the query to modify
     * @param array         $rows  the rows of subordinate resources
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
     * Generates the name of the foreign key column referencing this resource.
     * @return string
     */
    private static function foreignKey(): string
    {
        $self = Application::uqClass(get_called_class());

        if ($self === 'Subjects') {
            return 'subjectID';
        }

        if ($self === 'Programs') {
            return 'programID';
        }

        if ($self === 'Pools') {
            return 'poolID';
        }

        return '';
    }

    /**
     * Attempt to determine the left value for the range to be created
     *
     * @param null|int $parentID the parent of the item to be inserted
     * @param int      $ordering the targeted ordering on completion
     *
     * @return int  int the left value for the range to be created, or 0 on error
     */
    private static function left(?int $parentID, int $ordering): int
    {
        if (!$parentID) {
            $query = DB::query();
            $query->select('MAX(' . DB::qn('rgt') . ') + 1')->from(DB::qn('#__organizer_curricula'));
            DB::set($query);

            return DB::integer();
        }

        // Right value of the next lowest sibling
        $rgtQuery = DB::query();
        $rgtQuery->select('MAX(' . DB::qn('rgt') . ')')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('parentID') . ' = :parentID')->bind(':parentID', $parentID, ParameterType::INTEGER)
            ->where(DB::qn('ordering') . ' < :ordering')->bind(':ordering', $ordering, ParameterType::INTEGER);
        DB::set($rgtQuery);

        if ($rgt = DB::integer()) {
            return $rgt + 1;
        }

        // No siblings => use parent left for reference
        $lftQuery = DB::query();
        $lftQuery->select(DB::qn('lft'))
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('id') . ' = :parentID')->bind(':parentID', $parentID, ParameterType::INTEGER);
        DB::set($lftQuery);
        $lft = DB::integer();

        return $lft ? $lft + 1 : 0;
    }

    /**
     * Retrieves the existing ordering of a pool to its parent item, or next highest value in the series
     *
     * @param int $parentID   the id of the parent range
     * @param int $resourceID the id of the resource
     *
     * @return int  the value of the highest existing ordering or 1 if none exist
     */
    public static function ordering(int $parentID, int $resourceID): int
    {
        $column = self::foreignKey();
        $query  = DB::query();
        $query->select(DB::qn('ordering'))
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qcs([['parentID', $parentID], [$column, $resourceID]]));
        DB::set($query);

        if ($existingOrdering = DB::integer()) {
            return $existingOrdering;
        }

        $query = DB::query();
        $query->select('MAX(' . DB::qn('ordering') . ')')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qc('parentID', $parentID));
        DB::set($query);

        return DB::integer() + 1;
    }

    /**
     * Extracts the parent ids from an array of rows.
     *
     * @param array $rows the rows to filter
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
     * @param int $resourceID the id of the resource
     *
     * @return string  string representing the associated program(s)
     */
    public static function programName(int $resourceID): string
    {
        if (!$programs = self::programs($resourceID)) {
            return Text::_('NO_PROGRAMS');
        }

        if (count($programs) === 1) {
            return Programs::name($programs[0]['programID']);
        }
        else {
            return Text::_('MULTIPLE_PROGRAMS');
        }
    }

    /**
     * Looks up the names of the programs associated with the resource. Overwritten by the programs helper to prevent endless
     * regression.
     *
     * @param array|int $identifiers rows of subordinate resources | resource id
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
     * @param int $rowID the id of the row requested
     *
     * @return array
     */
    public static function row(int $rowID): array
    {
        $query = DB::query();
        $query->select('*')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('id') . ' = :rowID')
            ->bind(':rowID', $rowID, ParameterType::INTEGER);
        DB::set($query);

        return DB::array();
    }

    /**
     * Gets the curricula row ids specific to the resource of the calling class.
     *
     * @param int $resourceID the resource ID
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
     * @param array|int $identifiers rows of subordinate resources | resource id
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
     * Shifts the ordering for existing siblings who have an ordering at or above the ordering to be inserted.
     *
     * @param int $parentID the id of the parent
     * @param int $ordering the ordering of the item to be inserted
     *
     * @return bool  true on success, otherwise false
     */
    private static function shiftDown(int $parentID, int $ordering): bool
    {
        $column = DB::qn('ordering');
        $query  = DB::query();
        $query->update(DB::qn('#__organizer_curricula'))
            ->set("$column = $column - 1")
            ->where("$column > :ordering")
            ->bind(':ordering', $ordering, ParameterType::INTEGER)
            ->where(DB::qn('parentID') . ' = :parentID')
            ->bind(':parentID', $parentID, ParameterType::INTEGER);
        DB::set($query);

        return DB::execute();
    }

    /**
     * Shifts left and right values to allow for the values to be inserted
     *
     * @param int $left  the int value above which left and right values need to be shifted
     * @param int $width the width of the item being deleted
     *
     * @return bool  true on success, otherwise false
     */
    private static function shiftLeft(int $left, int $width): bool
    {
        $column = DB::qn('lft');
        $query  = DB::query();
        $query->update(DB::qn('#__organizer_curricula'))
            ->set("$column = $column - :width")->bind(':width', $width, ParameterType::INTEGER)
            ->where("$column > :left")->bind(':left', $left, ParameterType::INTEGER);
        DB::set($query);

        if (!DB::execute()) {
            return false;
        }

        $column = DB::qn('rgt');
        $query  = DB::query();
        $query->update(DB::qn('#__organizer_curricula'))
            ->set("$column = $column - :width")->bind(':width', $width, ParameterType::INTEGER)
            ->where("$column > :left")->bind(':left', $left, ParameterType::INTEGER);
        DB::set($query);

        return DB::execute();
    }

    /**
     * Shifts left and right values to allow for the values to be inserted
     *
     * @param int $left      the int value above which left and right values
     *                       need to be shifted
     *
     * @return bool  true on success, otherwise false
     */
    private static function shiftRight(int $left): bool
    {
        $column = DB::qn('lft');
        $query  = DB::query();
        $query->update(DB::qn('#__organizer_curricula'))
            ->set("$column = $column + 2")
            ->where("$column >= :left")->bind(':left', $left, ParameterType::INTEGER);
        DB::set($query);

        if (!DB::execute()) {
            return false;
        }

        $column = DB::qn('rgt');
        $query  = DB::query();
        $query->update(DB::qn('#__organizer_curricula'))
            ->set("$column = $column + 2")
            ->where("$column >= :left")->bind(':left', $left, ParameterType::INTEGER);
        DB::set($query);

        return DB::execute();
    }

    /**
     * Shifts the ordering for existing siblings who have an ordering at or above the ordering to be inserted.
     *
     * @param int $parentID the id of the parent
     * @param int $ordering the ordering of the item to be inserted
     *
     * @return bool  true on success, otherwise false
     */
    public static function shiftUp(int $parentID, int $ordering): bool
    {
        $column = DB::qn('ordering');
        $query  = DB::query();
        $query->update(DB::qn('#__organizer_curricula'))
            ->set("$column = $column + 1")
            ->where("$column >= :ordering")->bind(':ordering', $ordering, ParameterType::INTEGER)
            ->where(DB::qn('parentID') . ' = :parentID')->bind(':parentID', $parentID, ParameterType::INTEGER);
        DB::set($query);

        return DB::execute();
    }

    /**
     * Gets the curriculum for a pool selected as a subordinate resource
     *
     * @param int $poolID the resource id
     *
     * @return array[]  empty if no child data exists
     */
    protected static function subCurriculum(int $poolID): array
    {
        // Subordinate structures are the same for every superordinate resource
        $query = DB::query();
        $query->select(DB::qn('id'))
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('poolID') . ' = :poolID')->bind(':poolID', $poolID, ParameterType::INTEGER);
        DB::set($query);

        if (!$firstID = DB::integer()) {
            return [];
        }

        $query = DB::query();
        $query->select('*')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('parentID') . ' = :firstID')->bind(':firstID', $firstID, ParameterType::INTEGER)
            ->order(DB::qn('lft'));
        DB::set($query);

        if (!$subOrdinates = DB::arrays()) {
            return $subOrdinates;
        }

        foreach ($subOrdinates as $key => $subOrdinate) {
            if ($subOrdinate['poolID']) {
                $subOrdinates[$key]['curriculum'] = self::subCurriculum($subOrdinate['poolID']);
            }
        }

        return $subOrdinates;
    }

    /**
     * Finds the subject mappings subordinate to a particular resource in the curricula table.
     *
     * @param int $resourceID the id of the resource
     *
     * @return array[] the associated programs
     */
    public static function subjects(int $resourceID): array
    {
        $query = DB::query();
        $query->select('DISTINCT *')
            ->from(DB::qn('#__organizer_curricula'))
            ->order(DB::qn('lft'));

        /** @var Pools|Programs $resource */
        $resource = get_called_class();
        self::filterSubjects($query, $resource::rows($resourceID));

        DB::set($query);

        return DB::arrays();
    }

    /**
     * Builds the resource's curriculum using the subordinate resources contained in the form.
     * @return array[]
     */
    public static function subordinates(): array
    {
        if (Application::uqClass(get_called_class()) === 'Subjects') {
            return [];
        }

        $index        = 1;
        $subOrdinates = [];

        while (Input::integer("sub{$index}Order")) {
            $ordering      = Input::integer("sub{$index}Order");
            $aggregateInfo = Input::cmd("sub$index");

            if (!empty($aggregateInfo)) {
                $resourceID   = substr($aggregateInfo, 0, strlen($aggregateInfo) - 1);
                $resourceType = strpos($aggregateInfo, 'p') ? 'pool' : 'subject';

                if ($resourceType == 'subject') {
                    $subOrdinates[$ordering]['poolID']    = null;
                    $subOrdinates[$ordering]['subjectID'] = $resourceID;
                    $subOrdinates[$ordering]['ordering']  = $ordering;
                }

                if ($resourceType == 'pool') {
                    $subOrdinates[$ordering]['poolID']     = $resourceID;
                    $subOrdinates[$ordering]['subjectID']  = null;
                    $subOrdinates[$ordering]['ordering']   = $ordering;
                    $subOrdinates[$ordering]['curriculum'] = self::subCurriculum($resourceID);
                }
            }

            $index++;
        }

        return $subOrdinates;
    }

    /**
     * Retrieves a list of options for choosing superordinate entries in the curriculum hierarchy.
     *
     * @param int    $resourceID the id of the resource for which the form is being displayed
     * @param string $type       the type of the resource
     * @param array  $ranges     the rows for programs selected in the form, or already mapped
     *
     * @return stdClass[] the superordinate resource options
     */
    public static function superOptions(int $resourceID, string $type, array $ranges): array
    {
        $default           = HTML::option(-1, Text::_('NONE'));
        $default->disable  = '';
        $default->selected = '';

        if (!$ranges or !$type) {
            return [$default];
        }

        $rows = self::allRows($ranges);

        // The programs have no subordinate resources and subjects cannot be directly subordinated to programs
        if (count($rows) === count($ranges) and $type == 'subject') {
            return [$default];
        }

        $selected = [];

        if ($resourceID) {
            if ($type === 'pool') {
                $selected = Pools::rows($resourceID);

                // Remove subordinate pools from the available options
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
        $options   = [$default];

        foreach ($rows as $row) {
            if ($option = empty($row['poolID']) ? Programs::option($row, $parentIDs, $type) : Pools::option($row, $parentIDs)) {
                $options[] = $option;
            }
        }

        return $options;
    }

    /**
     * Gets the current superordinate ids for the given resource id and type.
     *
     * @param int    $id   the id of the resource in its respective table
     * @param string $type the type of subordinate resource (pool|subject)
     *
     * @return int[]
     */
    public static function superValues(int $id, string $type): array
    {
        if ($type === 'subject') {
            return self::parentIDs(Subjects::rows($id)) ?: [-1];
        }

        $rows     = self::allRows(Pools::programs($id));
        $selected = Pools::rows($id);

        foreach ($rows as $key => $row) {
            foreach ($selected as $sRange) {
                if ($row['lft'] >= $sRange ['lft'] and $row['rgt'] <= $sRange ['rgt']) {
                    unset($rows[$key]);
                }
            }
        }

        return self::parentIDs($selected) ?: [-1];
    }

    /**
     * Updates structure relations based on the superordinates selected in the form.
     *
     * @param array $data
     * @return void
     */
    public static function updateSuperOrdinates(array $data): void
    {
        $class = Application::uqClass(get_called_class());

        // Programs cannot have superordinate resources, hard unsupported error.
        if ($class === 'Programs') {
            Application::error(501);
        }


        // No program context was selected implicitly or explicitly => remove resource from curricula.
        if (empty($data['programIDs']) or in_array(self::NONE, $data['programIDs'])) {
            self::deleteRanges($data['id']);

            return;
        }

        // No superordinates were selected implicitly or explicitly => remove resource from curricula.
        if (empty($data['superordinates']) or in_array(self::NONE, $data['superordinates'])) {
            self::deleteRanges($data['id']);

            return;
        }

        // Retrieve the program context ranges for sanity checks on pool ranges
        $programRanges = [];
        foreach ($data['programIDs'] as $programID) {
            if ($ranges = Programs::rows($programID)) {
                $programRanges[$programID] = $ranges[0];
            }
        }

        // Selected program does not deliver a valid context for curriculum structures => remove resource from curricula.
        if (empty($programRanges)) {
            Application::message('412');
            self::deleteRanges($data['id']);

            return;
        }

        $soRanges = [];
        foreach ($data['superordinates'] as $soID) {
            $table = new Table();

            // Selected superordinate does not exist or is a subject mapping => invalid.
            if (!$table->load($soID) or $table->subjectID) {
                continue;
            }

            // Requested superordinate is the program context root
            if ($programID = $table->programID) {

                // Subjects may not be directly subordinate to programs => invalid.
                if ($class === 'Subjects') {
                    continue;
                }

                // Add every mapping for the selected program as a superordinate implicitly => not just those explicitly selected.
                foreach ($programRanges as $programRange) {
                    if ($programRange['programID'] === $programID) {
                        $soRanges[$programRange['id']] = $programRange;
                    }
                }

                continue;
            }

            // Add every mapping for the selected pool as a superordinate implicitly => not just those explicitly selected.
            foreach (Pools::rows($table->poolID) as $poolRange) {
                foreach ($programRanges as $programRange) {
                    $validLeft  = $poolRange['lft'] > $programRange['lft'];
                    $validRight = $poolRange['rgt'] < $programRange['rgt'];
                    if ($validLeft and $validRight) {
                        $soRanges[$poolRange['id']] = $poolRange;
                    }
                }
            }
        }

        // Selected superordinates were non-existent or invalid => remove resource from curricula.
        if (empty($soRanges)) {
            Application::message('412');
            self::deleteRanges($data['id']);

            return;
        }

        if (!self::addSubordinate($data, $soRanges)) {
            Application::message('UPDATE_CURRICULUM_FAILED', Application::WARNING);
            return;
        }

        $soIDs = array_keys($soRanges);

        foreach (self::rows($data['id']) as $range) {

            // Existing subordinate relation not in request => deprecated.
            if (!in_array($range['parentID'], $soIDs)) {
                self::deleteRange($range['id']);
            }
        }
    }
}