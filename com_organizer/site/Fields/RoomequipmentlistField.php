<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Fields;

use Joomla\CMS\Factory;
use Organizer\Adapters\Database;
use Organizer\Helpers;
use Organizer\Helpers\HTML;
use Organizer\Helpers\Languages;

/**
 * Class creates a form field for room type selection
 */
class RoomequipmentlistField extends OptionsField
{
    /**
     * @var  string
     */
    protected $type = 'Roomequipmentlist';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     */
    protected function getOptions(): array
    {
        $options = [];
        foreach ($this->getRoomResources() as $type)
        {
            $options[] = HTML::_('select.option', $type['id'], $type['name']);
        }

        $default_options   = parent::getOptions();
        return array_merge($default_options, $options);
    }

    function getRoomResources(){
        $tag = Languages::getTag();
        $app = Factory::getApplication();
        $db = Factory::getDbo();
        $query = Database::getQuery(true);
        $query->select("DISTINCT t.*, t.id AS id, t.name_$tag AS name")
            ->from('#__organizer_equipment AS t');
      /* displays the roomtype equipment in roomequipment select dropdown */
     /* $room_id = $app->input->getInt('id',0);
        $view = $app->input->get('view','');
        if($room_id > 0 && in_array($view,array('room_edit'))){

           $query->leftJoin('#__organizer_roomtype_equipment AS r_type_equipment ON r_type_equipment.equipmentID = t.id');
            //$query->leftJoin('#__organizer_roomtypes AS r_type ON r_type.id = r_type_equipment.roomtypeID');
            $query->leftJoin('#__organizer_rooms AS r_room ON r_type_equipment.roomtypeID = r_room.roomtypeID');
             $query->where('r_room.id ='.$db->q($room_id));
        }*/

        $query->order('name');
        Database::setQuery($query);

        return Database::loadAssocList('id');
    }
}
