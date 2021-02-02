<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @author      Wolf Rost, <wolf.rost@mni.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Tables;

/**
 * Models the organizer_programs table.
 */
class Programs extends BaseTable
{
	use Activated, Aliased;

	/**
	 * The year in which the program was accredited.
	 * YEAR(4) DEFAULT NULL
	 *
	 * @var int
	 */
	public $accredited;

	/**
	 * The id of the category entry referenced.
	 * INT(11) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $categoryID;

	/**
	 * An abbreviated nomenclature for the resource. Currently corresponding to the identifier in the LSF application.
	 * VARCHAR(60) DEFAULT NULL
	 *
	 * @var string
	 */
	public $code;

	/**
	 * The id of the degree entry referenced.
	 * INT(11) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $degreeID;

	/**
	 * The resource's German description.
	 * TEXT
	 *
	 * @var string
	 */
	public $description_de;

	/**
	 * The resource's English description.
	 * TEXT
	 *
	 * @var string
	 */
	public $description_en;

	/**
	 * A flag which displays whether the program has a fee.
	 * TINYINT(1) UNSIGNED NOT NULL
	 *
	 * @var string
	 */
	public $fee;

	/**
	 * The id of the frequency entry referenced.
	 * INT(1) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $frequencyID;

	/**
	 * The resource's German name.
	 * VARCHAR(150) NOT NULL
	 *
	 * @var string
	 */
	public $name_de;

	/**
	 * The resource's English name.
	 * VARCHAR(150) NOT NULL
	 *
	 * @var string
	 */
	public $name_en;

	/**
	 * A flag which displays whether the program has a restricted number of participants.
	 * TINYINT(1) UNSIGNED NOT NULL
	 *
	 * @var string
	 */
	public $nc;

	/**
	 * A flag which displays whether the program has special participation requirements
	 * TINYINT(1) UNSIGNED NOT NULL
	 *
	 * @var string
	 */
	public $special;

	/**
	 * Declares the associated table.
	 */
	public function __construct()
	{
		parent::__construct('#__organizer_programs');
	}

	/**
	 * Set the table column names which are allowed to be null
	 *
	 * @return bool  true
	 */
	public function check()
	{
		if (empty($this->alias))
		{
			$this->alias = null;
		}

		if (empty($this->categoryID))
		{
			$this->categoryID = null;
		}

		return true;
	}
}
