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

use Joomla\CMS\Factory;
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
		if ($this->selected)
		{
			foreach ($this->selected as $selectedID)
			{
				$room = new Tables\Rooms();

				if ($room->load($selectedID))
				{
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
		if (!Helpers\Can::manage('facilities'))
		{
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
		if ($this->selected)
		{
			foreach ($this->selected as $selectedID)
			{
				$room = new Tables\Rooms();

				if ($room->load($selectedID))
				{
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
		if (!$this->updateReferencingTable('monitors'))
		{
			return false;
		}

		return $this->updateIPReferences();
	}
    public function save($data = [])
    {
        $this->authorize();

        $data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;

        try
        {
            $table = $this->getTable();
        }
        catch (Exception $exception)
        {
            Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

            return false;
        }

        $id = $table->save($data) ? $table->id : false;
        if($id > 0){
            $db = Factory::getDbo();
            $query = $db->getQuery(true);
            $query->select('equipmentID')->from('#__organizer_room_equipment')->where('roomID='.$id);
            $db->setQuery($query);
            $saved_roomequipment = $db->loadObjectList();

            if(isset($data['equipment_list']) && !empty($data['equipment_list'])){
                foreach ($data['equipment_list'] as $equ){
                    foreach ($saved_roomequipment as $save_key => $save_equ){
                        if($save_equ->equipmentID == $equ['equipment']){
                           unset($saved_roomequipment[$save_key]);
                        }
                    }
                    $equipment_table = new Tables\Equipment();
                    if($equipment_table->load($equ['equipment'])){
                        $room_equipment_table = new Tables\RoomEquipment();
                        $room_equipment_table->load(array('roomID' => $id,'equipmentID' => $equ['equipment']));
                        $room_equipment_table->roomID = $id;
                        $room_equipment_table->equipmentID = $equ['equipment'];
                        $room_equipment_table->quantity = $equ['qty'];
                        $room_equipment_table->name_en = $equipment_table->name_en;
                        $room_equipment_table->name_de = $equipment_table->name_de;
                        $room_equipment_table->description_de = $equipment_table->description_de;
                        $room_equipment_table->description_en = $equipment_table->description_en;
                        $room_equipment_table->store();
                    }
                }
            }

            foreach ($saved_roomequipment as $saved_equipment){
                $room_equipment_table = new Tables\RoomEquipment();
                $room_equipment_table->load(array('roomID' => $id,'equipmentID' => $saved_equipment->equipmentID));
                $room_equipment_table->delete();
            }
        }
        return $id;
    }
}
