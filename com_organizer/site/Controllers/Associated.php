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

use THM\Organizer\Adapters\Application;
use THM\Organizer\Helpers\Organizations;
use THM\Organizer\Tables\Associations;

/**
 * Standardizes maintenance of associations entries across resources.
 */
trait Associated
{
    /**
     * Updates associations entries for the resource in the save context. Not to be confused with updateAssociations in
     * the merge context.
     *
     * @return bool
     * @see MergeController::updateAssociations()
     */
    protected function updateAssociations(): bool
    {
        $column     = strtolower(Application::getClass(get_called_class())) . 'ID';
        $resourceID = $this->data['id'];
        foreach (Organizations::getIDs() as $organizationID) {

            $association = new Associations();
            $keys        = [$column => $resourceID, 'organizationID' => $organizationID];

            $exists   = $association->load($keys);
            $unwanted = !in_array($organizationID, $this->data['organizationIDs']);

            // Either way no save event
            if ($exists or $unwanted) {

                // Deprecated
                if ($exists and $unwanted and !$association->delete()) {
                    return false;
                }

                continue;
            }

            if (!$association->save($keys)) {
                return false;
            }
        }

        return true;
    }
}
