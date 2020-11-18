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
 * Models the organizer_instances table.
 */
class Bookings extends BaseTable
{
	/**
	 * The id of the block entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $blockID;

	/**
	 * A code used for participants to check into instances for this booking.
	 * VARCHAR(60) NOT NULL
	 *
	 * @var string
	 */
	public $code;

	/**
	 * Textual notes to the execution of this booking.
	 * TEXT
	 *
	 * @var string
	 */
	public $notes;

	/**
	 * The id of the unit entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $unitID;

	/**
	 * Declares the associated table.
	 */
	public function __construct()
	{
		parent::__construct('#__organizer_bookings');
	}
}
