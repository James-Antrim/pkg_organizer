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
 * Models the organizer_subject_events table.
 */
class SubjectEvents extends BaseTable
{
	/**
	 * The id of the event entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $eventID;

	/**
	 * The id of the subject entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $subjectID;

	/**
	 * Declares the associated table
	 *
	 * @param   JDatabaseDriver  $dbo  A database connector object
	 */
	public function __construct($dbo = null)
	{
		parent::__construct('#__organizer_subject_events', 'id', $dbo);
	}
}
