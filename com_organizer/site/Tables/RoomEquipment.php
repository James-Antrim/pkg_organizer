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
 * Models the organizer_instance_persons table.
 */
class RoomEquipment extends BaseTable
{
	/**
	 * The description of the specific equipment referenced: make, model, color, ...
	 * VARCHAR(255)     NOT NULL DEFAULT ''
	 *
	 * @var string
	 */
	public $description;

	/**
	 * The id of the equipment entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $equipmentID;

	/**
	 * The id of the room entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $roomID;

	/**
	 * The quantity of the referenced equipment in the room.
	 * INT(4) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $quantity;

	/**
	 * Declares the associated table.
	 */
	public function __construct()
	{
		parent::__construct('#__organizer_room_equipment');
	}
}
