<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Organizer\Adapters\Database;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored room data.
 */
class Room extends MergeModel
{
    /**
     * Activates rooms by id if a selection was made, otherwise by use in the instance_rooms table.
     *
     * @return bool true on success, otherwise false
     */
    public function activate()
    {
        $this->selected = Helpers\Input::getSelectedIDs();
        $this->authorize();

        // Explicitly selected resources
        if ($this->selected) {
            foreach ($this->selected as $selectedID) {
                $room = new Tables\Rooms();

                if ($room->load($selectedID)) {
                    $room->active = 1;
                    $room->store();
                    continue;
                }

                return false;
            }

            return true;
        }

        // Implicitly used resources
        $subQuery = Database::getQuery(true);
        $subQuery->select('DISTINCT roomID')->from('#__organizer_instance_rooms');
        $query = Database::getQuery(true);
        $query->update('#__organizer_rooms')->set('active = 1')->where("id IN ($subQuery)");
        Database::setQuery($query);

        return Database::execute();
    }

    /**
     * @inheritDoc
     */
    protected function authorize()
    {
        if (!Helpers\Can::manage('facilities')) {
            Helpers\OrganizerHelper::error(403);
        }
    }

    /**
     * Deactivates rooms by id if a selection was made, otherwise by lack of use in the instance_rooms table.
     *
     * @return bool true on success, otherwise false
     */
    public function deactivate()
    {
        $this->selected = Helpers\Input::getSelectedIDs();
        $this->authorize();

        // Explicitly selected resources
        if ($this->selected) {
            foreach ($this->selected as $selectedID) {
                $room = new Tables\Rooms();

                if ($room->load($selectedID)) {
                    $room->active = 0;
                    $room->store();
                    continue;
                }

                return false;
            }

            return true;
        }

        // Implicitly unused resources
        $subQuery = Database::getQuery(true);
        $subQuery->select('DISTINCT roomID')->from('#__organizer_instance_rooms');
        $query = Database::getQuery();
        $query->update('#__organizer_rooms')->set('active = 0')->where("id NOT IN ($subQuery)");
        Database::setQuery($query);

        return Database::execute();
    }

    /**
     * @inheritDoc
     */
    public function getTable($name = '', $prefix = '', $options = [])
    {
        return new Tables\Rooms();
    }

    /**
     * @inheritDoc
     */
    protected function updateReferences()
    {
        if (!$this->updateReferencingTable('monitors')) {
            return false;
        }

        return $this->updateIPReferences();
    }
}
