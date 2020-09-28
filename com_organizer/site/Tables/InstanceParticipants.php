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
 * Models the organizer_instance_participants table.
 */
class InstanceParticipants extends BaseTable
{
	/**
	 * The id of the instance entry referenced.
	 * INT(20) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $instanceID;

	/**
	 * The id of the participant entry referenced.
	 * INT(11) NOT NULL
	 *
	 * @var int
	 */
	public $participantID;

	/**
	 * Declares the associated table.
	 */
	public function __construct()
	{
		parent::__construct('#__organizer_instance_participants');
	}
}
