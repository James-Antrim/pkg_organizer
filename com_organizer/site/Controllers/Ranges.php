<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2024 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer\Controllers;

use Joomla\Database\ParameterType;
use THM\Organizer\Adapters\{Application, Database as DB, Input};
use THM\Organizer\Helpers\{Curricula as Helper, Pools, Programs};
use THM\Organizer\Tables\Curricula;

/**
 * Encapsulates functions dealing with curriculum table ranges.
 */
trait Ranges
{
    /**
     * Adds a curriculum range to a parent curriculum range
     *
     * @param   array &$range  an array containing data about a curriculum item and potentially its children
     *
     * @return int the id of the curriculum row on success, otherwise 0
     */
    protected function addRange(array &$range): int
    {
        $curricula = new Curricula();

        if (empty($range['programID'])) {
            // Subordinates must have a parent
            if (empty($range['parentID']) or !$parent = Helper::row($range['parentID'])) {
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

            $range['lft'] = $this->left($range['parentID'], $range['ordering']);

            if (!$range['lft'] or !$this->shiftRight($range['lft'])) {
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

                if (!$subRangeID = $this->addRange($subOrdinate)) {
                    return 0;
                }

                $subRangeIDs[$subRangeID] = $subRangeID;
            }

            if ($subRangeIDs) {
                $query = DB::getQuery();
                $query->select(DB::qn('id'))
                    ->from(DB::qn('#__organizer_curricula'))
                    ->whereNotIn(DB::qn('id'), $subRangeIDs)
                    ->where(DB::qn('parentID') . ' = :curriculaID')
                    ->bind(':curriculaID', $curricula->id, ParameterType::INTEGER);
                DB::setQuery($query);

                if ($zombieIDs = DB::loadIntColumn()) {
                    foreach ($zombieIDs as $zombieID) {
                        $this->deleteRange($zombieID);
                    }
                }
            }
        }

        return $curricula->id;
    }

    /**
     * Adds ranges for the resource to the given superordinate ranges.
     *
     * @param   array  $data            the resource data from the form
     * @param   array  $superOrdinates  the valid superordinate ranges to which to create/validate ranges within
     *
     * @return bool
     */
    protected function addSubordinate(array $data, array $superOrdinates): bool
    {
        switch (Application::getClass(get_called_class())) {
            case 'Pool':
                $range = [
                    'poolID'     => $data['id'],
                    'curriculum' => Input::getTask() !== 'Pool.save2copy' ? $this->subordinates() : []
                ];
                break;
            case 'Subject':
                $range = [
                    'subjectID'  => $data['id'],
                    'curriculum' => []
                ];
                break;
            default:
                return false;
        }

        $ranges = $this->ranges($data['id']);

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

                        $this->addRange($subOrdinate);
                    }

                    continue 2;
                }
            }

            $range['ordering'] = $this->ordering($super['id'], $data['id']);

            if (!$this->addRange($range)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gets the curriculum for a pool selected as a subordinate resource
     *
     * @param   int  $poolID  the resource id
     *
     * @return array[]  empty if no child data exists
     */
    protected function curriculum(int $poolID): array
    {
        // Subordinate structures are the same for every superordinate resource
        $query = DB::getQuery();
        $query->select(DB::qn('id'))
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('poolID') . ' = :poolID')->bind(':poolID', $poolID, ParameterType::INTEGER);
        DB::setQuery($query);

        if (!$firstID = DB::loadInt()) {
            return [];
        }

        $query = DB::getQuery();
        $query->select('*')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('parentID') . ' = :firstID')->bind(':firstID', $firstID, ParameterType::INTEGER)
            ->order(DB::qn('lft'));
        DB::setQuery($query);

        if (!$subOrdinates = DB::loadAssocList()) {
            return $subOrdinates;
        }

        foreach ($subOrdinates as $key => $subOrdinate) {
            if ($subOrdinate['poolID']) {
                $subOrdinates[$key]['curriculum'] = $this->curriculum($subOrdinate['poolID']);
            }
        }

        return $subOrdinates;
    }

    /**
     * Method to delete resource ranges not subordinate to the given superordinate elements.
     *
     * @param   int    $resourceID      the resource id
     * @param   array  $superOrdinates  the valid superordinate ranges
     *
     * @return void
     */
    protected function deleteDeprecated(int $resourceID, array $superOrdinates): void
    {
        $superIDs = array_keys($superOrdinates);

        foreach ($this->ranges($resourceID) as $range) {
            if (in_array($range['parentID'], $superIDs)) {
                continue;
            }

            // Remove unrequested existing relationship
            $this->deleteRange($range['id']);
        }
    }

    /**
     * Method to delete a single range from the curricula table
     *
     * @param   int  $rangeID  the id value of the range to be deleted
     *
     * @return bool  true on success, otherwise false
     */
    protected function deleteRange(int $rangeID): bool
    {
        if (!$range = Helper::row($rangeID)) {
            return false;
        }

        // Deletes the range
        $curricula = new Curricula();

        if (!$curricula->delete($rangeID)) {
            return false;
        }

        // Reduces the ordering of siblings with a greater ordering
        if (!empty($range['parentID']) and !$this->shiftDown($range['parentID'], $range['ordering'])) {
            return false;
        }

        $width = $range['rgt'] - $range['lft'] + 1;

        return $this->shiftLeft($range['lft'], $width);
    }

    /**
     * Deletes ranges of a specific curriculum resource.
     *
     * @param   int  $resourceID  the id of the resource
     *
     * @return bool true on success, otherwise false
     */
    protected function deleteRanges(int $resourceID): bool
    {
        $helper = Application::getClass(get_called_class());
        $helper = "THM\\Organizer\\Helpers\\" . $helper;
        $helper = str_ends_with($helper, 's') ? $helper : $helper . 's';

        /** @var Helper $helper */
        if ($rangeIDs = $helper::rowIDs($resourceID)) {
            foreach ($rangeIDs as $rangeID) {
                $success = $this->deleteRange($rangeID);
                if (!$success) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Attempt to determine the left value for the range to be created
     *
     * @param   null|int  $parentID  the parent of the item to be inserted
     * @param   int       $ordering  the targeted ordering on completion
     *
     * @return int  int the left value for the range to be created, or 0 on error
     */
    protected function left(?int $parentID, int $ordering): int
    {
        if (!$parentID) {
            $query = DB::getQuery();
            $query->select('MAX(' . DB::qn('rgt') . ') + 1')->from(DB::qn('#__organizer_curricula'));
            DB::setQuery($query);

            return DB::loadInt();
        }

        // Right value of the next lowest sibling
        $rgtQuery = DB::getQuery();
        $rgtQuery->select('MAX(' . DB::qn('rgt') . ')')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('parentID') . ' = :parentID')->bind(':parentID', $parentID, ParameterType::INTEGER)
            ->where(DB::qn('ordering') . ' < :ordering')->bind(':ordering', $ordering, ParameterType::INTEGER);
        DB::setQuery($rgtQuery);

        if ($rgt = DB::loadInt()) {
            return $rgt + 1;
        }

        // No siblings => use parent left for reference
        $lftQuery = DB::getQuery();
        $lftQuery->select(DB::qn('lft'))
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('id') . ' = :parentID')->bind(':parentID', $parentID, ParameterType::INTEGER);
        DB::setQuery($lftQuery);
        $lft = DB::loadInt();

        return $lft ? $lft + 1 : 0;
    }

    /**
     * Gets the mapped curricula ranges for the given resource
     *
     * @param   int  $resourceID  the resource id
     *
     * @return array[] the resource ranges
     */
    protected function ranges(int $resourceID): array
    {
        $helper = Application::getClass(get_called_class());
        $helper = "THM\\Organizer\\Helpers\\" . $helper;
        $helper = str_ends_with($helper, 's') ? $helper : $helper . 's';

        /** @var Helper $helper */
        return $helper::rows($resourceID);
    }

    /**
     * Shifts the ordering for existing siblings who have an ordering at or above the ordering to be inserted.
     *
     * @param   int  $parentID  the id of the parent
     * @param   int  $ordering  the ordering of the item to be inserted
     *
     * @return bool  true on success, otherwise false
     */
    private function shiftDown(int $parentID, int $ordering): bool
    {
        $column = DB::qn('ordering');
        $query  = DB::getQuery();
        $query->update(DB::qn('#__organizer_curricula'))
            ->set("$column = $column - 1")
            ->where("$column > :ordering")
            ->bind(':ordering', $ordering, ParameterType::INTEGER)
            ->where(DB::qn('parentID') . ' = :parentID')
            ->bind(':parentID', $parentID, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::execute();
    }

    /**
     * Shifts left and right values to allow for the values to be inserted
     *
     * @param   int  $left   the int value above which left and right values need to be shifted
     * @param   int  $width  the width of the item being deleted
     *
     * @return bool  true on success, otherwise false
     */
    private function shiftLeft(int $left, int $width): bool
    {
        $column = DB::qn('lft');
        $query  = DB::getQuery();
        $query->update(DB::qn('#__organizer_curricula'))
            ->set("$column = $column - :width")->bind(':width', $width, ParameterType::INTEGER)
            ->where("$column > :left")->bind(':left', $left, ParameterType::INTEGER);
        DB::setQuery($query);

        if (!DB::execute()) {
            return false;
        }

        $column = DB::qn('rgt');
        $query  = DB::getQuery();
        $query->update(DB::qn('#__organizer_curricula'))
            ->set("$column = $column - :width")->bind(':width', $width, ParameterType::INTEGER)
            ->where("$column > :left")->bind(':left', $left, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::execute();
    }

    /**
     * Shifts left and right values to allow for the values to be inserted
     *
     * @param   int  $left   the int value above which left and right values
     *                       need to be shifted
     *
     * @return bool  true on success, otherwise false
     */
    protected function shiftRight(int $left): bool
    {
        $column = DB::qn('lft');
        $query  = DB::getQuery();
        $query->update(DB::qn('#__organizer_curricula'))
            ->set('$column = $column + 2')
            ->where("$column >= :left")->bind(':left', $left, ParameterType::INTEGER);
        DB::setQuery($query);

        if (!DB::execute()) {
            return false;
        }

        $column = DB::qn('rgt');
        $query  = DB::getQuery();
        $query->update(DB::qn('#__organizer_curricula'))
            ->set('$column = $column 2')
            ->where("$column >= :left")->bind(':left', $left, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::execute();
    }

    /**
     * Shifts the ordering for existing siblings who have an ordering at or above the ordering to be inserted.
     *
     * @param   int  $parentID  the id of the parent
     * @param   int  $ordering  the ordering of the item to be inserted
     *
     * @return bool  true on success, otherwise false
     */
    protected function shiftUp(int $parentID, int $ordering): bool
    {
        $column = DB::qn('ordering');
        $query  = DB::getQuery();
        $query->update(DB::qn('#__organizer_curricula'))
            ->set('$column = $column + 1')
            ->where("$column >= :ordering")->bind(':ordering', $ordering, ParameterType::INTEGER)
            ->where(DB::qn('parentID') . ' = :parentID')->bind(':parentID', $parentID, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::execute();
    }

    /**
     * Builds the resource's curriculum using the subordinate resources contained in the form.
     * @return array[]
     */
    protected function subordinates(): array
    {
        if (Application::getClass(get_called_class()) === 'Subject') {
            return [];
        }

        $index        = 1;
        $subOrdinates = [];

        while (Input::getInt("sub{$index}Order")) {
            $ordering      = Input::getInt("sub{$index}Order");
            $aggregateInfo = Input::getCMD("sub$index");

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
                    $subOrdinates[$ordering]['curriculum'] = $this->curriculum($resourceID);
                }
            }

            $index++;
        }

        return $subOrdinates;
    }

    /**
     * Performs checks to ensure that a superordinate item has been selected as a precursor to the rest of the
     * curriculum processing.
     *
     * @param   array  $data  the form data
     *
     * @return array[] the applicable superordinate ranges
     */
    protected function superOrdinates(array $data): array
    {
        $class = Application::getClass(get_called_class());
        if ($class === 'Program') {
            Application::error(501);
        }

        // No program context was selected implicitly or explicitly.
        if (empty($data['curricula']) or in_array(self::NONE, $data['curricula'])) {
            $this->deleteRanges($data['id']);

            return [];
        }

        // No superordinates were selected implicitly or explicitly.
        if (empty($data['superordinates']) or in_array(self::NONE, $data['superordinates'])) {
            $this->deleteRanges($data['id']);

            return [];
        }

        // Retrieve the program context ranges for sanity checks on pool ranges
        $programRanges = [];
        foreach ($data['curricula'] as $programID) {
            if ($ranges = Programs::rows($programID)) {
                $programRanges[$programID] = $ranges[0];
            }
        }

        $soRanges = [];
        foreach ($data['superordinates'] as $soID) {
            $table = new Curricula();

            // Non-existent or invalid entry
            if (!$table->load($soID) or $table->subjectID) {
                continue;
            }

            // Requested superordinate is the program context root
            if ($programID = $table->programID) {
                // Subjects may not be directly associated with programs
                if ($class === 'Subject') {
                    continue;
                }

                foreach ($programRanges as $programRange) {
                    if ($programRange['programID'] === $programID) {
                        $soRanges[$programRange['id']] = $programRange;
                    }
                }

                continue;
            }

            foreach (Pools::rows($table->poolID) as $poolRange) {
                foreach ($programRanges as $programRange) {
                    // Pool range is a valid subset of the program context range
                    if ($poolRange['lft'] > $programRange['lft'] and $poolRange['rgt'] < $programRange['rgt']) {
                        $soRanges[$poolRange['id']] = $poolRange;
                    }
                }
            }
        }

        return $soRanges;
    }
}