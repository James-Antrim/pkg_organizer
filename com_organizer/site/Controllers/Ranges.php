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
}