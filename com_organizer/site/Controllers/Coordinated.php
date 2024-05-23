<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use THM\Organizer\Tables\EventCoordinators as Coordinator;

/**
 * Standard implementation for de-/activating resources.
 */
trait Coordinated
{

    /**
     * Updates the event coordinators table references to the mergeID.
     *
     * @param   string  $thisColumn   the name of the column referencing the calling resource context
     * @param   string  $otherColumn  the name of the column referencing the associated resource
     *
     * @return bool
     */
    private function updateCoordinators(string $thisColumn, string $otherColumn): bool
    {
        if (!$otherIDs = $this->getReferences('event_coordinators', $otherColumn)) {
            return true;
        }

        foreach ($otherIDs as $otherID) {
            $existing = null;

            foreach ($this->mergeIDs as $currentID) {
                $coordinator = new Coordinator();
                $keys        = [$thisColumn => $currentID, $otherColumn => $otherID];

                // The current personID is not associated with the current eventID
                if (!$coordinator->load($keys)) {
                    continue;
                }

                // An existing association with the current eventID has already been found, making this a duplicate.
                if ($existing) {
                    $coordinator->delete();
                    continue;
                }

                $coordinator->$thisColumn = $this->mergeID;
                $existing                 = $coordinator;
            }

            if ($existing and !$existing->store()) {
                return false;
            }
        }

        return true;
    }
}