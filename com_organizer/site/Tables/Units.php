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
 * Models the organizer_units table.
 */
class Units extends BaseTable
{
	use Modified;

	/**
	 * Currently corresponding to the identifier in Untis scheduling software.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var string
	 */
	public $code;

	/**
	 * A supplementary text description.
	 * VARCHAR(255) DEFAULT NULL
	 *
	 * @var string
	 */
	public $comment;

	/**
	 * The id of the course entry referenced.
	 * INT(11) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $courseID;

	/**
	 * The id of the organization entry referenced.
	 * INT(11) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $organizationID;

	/**
	 * The end date of the resource.
	 * DATE DEFAULT NULL
	 *
	 * @var string
	 */
	public $endDate;

	/**
	 * The id of the grid entry referenced.
	 * INT(11) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $gridID;

	/**
	 * The id of the run entry referenced.
	 * INT(11) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $runID;

	/**
	 * The start date of the resource.
	 * DATE DEFAULT NULL
	 *
	 * @var string
	 */
	public $startDate;

	/**
	 * The id of the term entry referenced.
	 * INT(11) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $termID;

	/**
	 * Declares the associated table.
	 */
	public function __construct()
	{
		parent::__construct('#__organizer_units');
	}

	/**
	 * Set the table column names which are allowed to be null
	 *
	 * @return bool  true
	 */
	public function check()
	{
		$nullColumns = ['courseID', 'endDate', 'gridID', 'runID', 'startDate'];

		foreach ($nullColumns as $nullColumn)
		{
			if (!$this->$nullColumn)
			{
				$this->$nullColumn = null;
			}
		}

		return true;
	}
}
