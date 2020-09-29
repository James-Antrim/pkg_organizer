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
 * Models the thm_organizer_calendar table.
 */
class Calendar extends BaseTable
{
	use Modified;

	/**
	 * The end time of the block.
	 * TIME DEFAULT NULL
	 *
	 * @var string
	 */
	public $endTime;

	/**
	 * The id of the unit entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $lessonID;

	/**
	 * The date of the block.
	 * DATE DEFAULT NULL
	 *
	 * @var string
	 */
	public $schedule_date;

	/**
	 * The start time of the block.
	 * TIME DEFAULT NULL
	 *
	 * @var string
	 */
	public $startTime;

	/**
	 * Declares the associated table.
	 */
	public function __construct()
	{
		parent::__construct('#__thm_organizer_calendar');
	}

	/**
	 * Set the table column names which are allowed to be null
	 *
	 * @return boolean  true
	 */
	public function check()
	{
		$this->modified = null;

		return true;
	}
}
