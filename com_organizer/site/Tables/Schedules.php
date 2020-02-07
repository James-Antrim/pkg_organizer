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

use JDatabaseDriver;

/**
 * Models the organizer_schedules table.
 */
class Schedules extends BaseTable
{
	use Activated;

	/**
	 * The date of the schedule's creation.
	 * DATE DEFAULT NULL
	 *
	 * @var string
	 */
	public $creationDate;

	/**
	 * The time of the schedule's creation.
	 * TIME DEFAULT NULL
	 *
	 * @var string
	 */
	public $creationTime;

	/**
	 * The id of the organization entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $organizationID;

	/**
	 * A collection of instance objects modeled by a JSON string.
	 * MEDIUMTEXT NOT NULL
	 *
	 * @var string
	 */
	public $schedule;

	/**
	 * The id of the term entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $termID;

	/**
	 * The id of the user entry referenced.
	 * INT(11) DEFAULT NULL
	 *
	 * @var int
	 */
	public $userID;

	/**
	 * Declares the associated table
	 *
	 * @param   JDatabaseDriver &$dbo  A database connector object
	 */
	public function __construct(&$dbo = null)
	{
		parent::__construct('#__organizer_schedules', 'id', $dbo);
	}
}
