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

use Organizer\Helpers;
use Organizer\Tables;
use Joomla\CMS\Factory;

/**
 * Class which manages stored room type data.
 */
class Roomtype extends BaseModel
{
	/**
	 * Authorizes the user.
	 *
	 * @return void
	 */
	protected function authorize()
	{
		if (!Helpers\Can::manage('facilities'))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Roomtypes A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Roomtypes;
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
            $query->select('equipmentID')->from('#__organizer_roomtype_equipment')->where('roomtypeID='.$id);
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
                        $room_equipment_table = new Tables\RoomTypeEquipment();
                        $room_equipment_table->load(array('roomtypeID' => $id,'equipmentID' => $equ['equipment']));
                        $room_equipment_table->roomtypeID = $id;
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
                $room_equipment_table = new Tables\RoomTypeEquipment();
                $room_equipment_table->load(array('roomtypeID' => $id,'equipmentID' => $saved_equipment->equipmentID));
                $room_equipment_table->delete();
            }
        }
        return $id;
    }
}
