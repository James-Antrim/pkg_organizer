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
 * Class instantiates a Table Object associated with the lesson_groups table.
 */
class Prerequisites extends BaseTable
{
	/**
	 * The id of the subject entry referenced as being a dependency.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $prerequisiteID;

	/**
	 * The id of the subject entry referenced as requiring a dependency.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $subjectID;

	/**
	 * Declares the associated table
	 *
	 * @param   \JDatabaseDriver &$dbo  A database connector object
	 */
	public function __construct(&$dbo = null)
	{
		parent::__construct('#__organizer_prerequisites', 'id', $dbo);
	}
}
