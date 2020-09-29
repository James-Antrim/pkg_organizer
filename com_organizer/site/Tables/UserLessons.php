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
 * Models the thm_organizer_user_lessons table.
 */
class UserLessons extends BaseTable
{
	/**
	 * A JSON string aggregating ccm ids which the user has added to their personal schedule.
	 * TEXT
	 *
	 * @var string
	 */
	public $configuration;

	/**
	 * The id of the unit entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $lessonID;

	/**
	 * The user's status null => irrelevant, 0 waitlist, 1 registered
	 * INT UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $status;

	/**
	 * The date and time of the last status change.
	 * DATETIME DEFAULT NULL
	 *
	 * @var string
	 */
	public $status_date;

	/**
	 * The date and time of the last participant initiated change.
	 * DATETIME DEFAULT NULL
	 *
	 * @var string
	 */
	public $user_date;

	/**
	 * The user id.
	 * INT SIGNED 11 NOT NULL
	 *
	 * @var int
	 */
	public $userID;

	/**
	 * Declares the associated table.
	 */
	public function __construct()
	{
		parent::__construct('#__thm_organizer_user_lessons');
	}
}
