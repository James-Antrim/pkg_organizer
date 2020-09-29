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
 * Models the thm_organizer_lesson_pools table.
 */
class LessonPools extends BaseTable
{
	use Modified;

	/**
	 * The id of the pool entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $poolID;

	/**
	 * The id of the lesson->subject entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $subjectID;

	/**
	 * Declares the associated table.
	 */
	public function __construct()
	{
		parent::__construct('#__thm_organizer_lesson_pools');
	}
}
