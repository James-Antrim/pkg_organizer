<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Tables;

/**
 * Models the organizer_rooms table.
 */
class RoomEquipment extends BaseTable
{
    public $name_de;
    public $name_en;
    public $description_de;
    public $description_en;
    public $quantity;
    public $roomID;
    public $equipmentID;
    public function __construct()
    {
        parent::__construct('#__organizer_room_equipment');
    }
}
