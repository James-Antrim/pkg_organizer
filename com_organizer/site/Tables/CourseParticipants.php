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
 * Models the organizer_course_participants table.
 */
class CourseParticipants extends BaseTable
{
	/**
	 * Whether or not the participant actually attended the course. Values: 0 - Unattended, 1 - Attended.
	 * TINYINT(1) UNSIGNED DEFAULT 0
	 *
	 * @var bool
	 */
	public $attended;

	/**
	 * The id of the course entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $courseID;

	/**
	 * The participant's course payment status. Values: 0 - Unpaid, 1 - Paid.
	 * TINYINT(1) UNSIGNED DEFAULT 0
	 *
	 * @var bool
	 */
	public $paid;

	/**
	 * The date and time of the last participant initiated change.
	 * DATETIME DEFAULT NULL
	 *
	 * @var string
	 */
	public $participantDate;

	/**
	 * The id of the participant entry referenced.
	 * INT(11) NOT NULL
	 *
	 * @var int
	 */
	public $participantID;

	/**
	 * The participant's course status. Values: 0 - Pending, 1 - Accepted.
	 * TINYINT(1) UNSIGNED DEFAULT 0
	 *
	 * @var int
	 */
	public $status;

	/**
	 * The date and time of the last change.
	 * DATETIME DEFAULT NULL
	 *
	 * @var string
	 */
	public $statusDate;

	/**
	 * Declares the associated table
	 *
	 * @param   JDatabaseDriver  $dbo  A database connector object
	 */
	public function __construct($dbo = null)
	{
		parent::__construct('#__organizer_course_participants', 'id', $dbo);
	}
}
