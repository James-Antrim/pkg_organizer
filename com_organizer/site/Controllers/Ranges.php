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

use THM\Organizer\Helpers\Curricula as Helper;

/**
 * Encapsulates functions dealing with curriculum table ranges.
 */
trait Ranges
{
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
        $helper = "THM\\Organizer\\Helpers\\$this->helper";

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
}