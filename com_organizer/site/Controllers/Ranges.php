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
use THM\Organizer\Adapters\Application;
use THM\Organizer\Adapters\Database as DB;
use THM\Organizer\Helpers\Curricula as Helper;
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
        /** @var Helper $helper */
        $helper = "THM\\Organizer\\Helpers\\" . Application::getClass(get_called_class());

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
        /** @var Helper $helper */
        $helper = "THM\\Organizer\\Helpers\\" . Application::getClass(get_called_class());

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
}