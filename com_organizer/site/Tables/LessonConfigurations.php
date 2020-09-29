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
 * Models the thm_organizer_lesson_configurations table.
 */
class LessonConfigurations extends BaseTable
{
	/**
	 * A configuration object modeled by a JSON string, containing the room/teacher configuration.
	 * TEXT
	 *
	 * @var string
	 */
	public $configuration;

	/**
	 * The id of the lesson->subject entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $lessonID;

	/**
	 * The timestamp at which the schedule was generated which modified this entry.
	 * TIMESTAMP DEFAULT CURRENT_TIMESTAMP
	 *
	 * @var int
	 */
	public $modified;

	/**
	 * Declares the associated table.
	 */
	public function __construct()
	{
		parent::__construct('#__thm_organizer_lesson_configurations');
	}
}
