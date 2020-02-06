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
 * Models the organizer_blocks table.
 */
class Blocks extends BaseTable
{
	/**
	 * The date of the block.
	 * DATE DEFAULT NULL
	 *
	 * @var string
	 */
	public $date;

	/**
	 * The numerical day of the week of the block.
	 * TINYINT(1) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $dow;

	/**
	 * The end time of the block.
	 * TIME DEFAULT NULL
	 *
	 * @var string
	 */
	public $endTime;

	/**
	 * The start time of the block.
	 * TIME DEFAULT NULL
	 *
	 * @var string
	 */
	public $startTime;

	/**
	 * Declares the associated table
	 *
	 * @param   \JDatabaseDriver &$dbo  A database connector object
	 */
	public function __construct(&$dbo = null)
	{
		parent::__construct('#__organizer_blocks', 'id', $dbo);
	}
}
