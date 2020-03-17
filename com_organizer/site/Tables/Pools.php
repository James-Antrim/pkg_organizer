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
 * Models the organizer_pools table.
 */
class Pools extends BaseTable
{
	use Aliased;

	/**
	 * The resource's German abbreviation.
	 * VARCHAR(25) NOT NULL DEFAULT ''
	 *
	 * @var string
	 */
	public $abbreviation_de;

	/**
	 * The resource's English abbreviation.
	 * VARCHAR(25) NOT NULL DEFAULT ''
	 *
	 * @var string
	 */
	public $abbreviation_en;

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
	 * The id of the field entry referenced.
	 * INT(11) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $fieldID;

	/**
	 * The id of the group entry referenced.
	 * INT(11) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $groupID;

	/**
	 * The id of the entry in the LSF software module.
	 * INT(11) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $lsfID;

	/**
	 * The maximum credit points required to be achieved in subjects of this pool.
	 * INT(3) UNSIGNED DEFAULT 0
	 *
	 * @var int
	 */
	public $maxCrP;

	/**
	 * The minimum credit points required to be achieved in subjects of this pool.
	 * INT(3) UNSIGNED DEFAULT 0
	 *
	 * @var int
	 */
	public $minCrP;

	/**
	 * The resource's German name.
	 * VARCHAR(255) NOT NULL
	 *
	 * @var string
	 */
	public $name_de;

	/**
	 * The resource's English name.
	 * VARCHAR(255) NOT NULL
	 *
	 * @var string
	 */
	public $name_en;

	/**
	 * The resource's German shortened name.
	 * VARCHAR(50) DEFAULT ''
	 *
	 * @var string
	 */
	public $shortName_de;

	/**
	 * The resource's English shortened name.
	 * VARCHAR(50) DEFAULT ''
	 *
	 * @var string
	 */
	public $shortName_en;

	/**
	 * Declares the associated table
	 *
	 * @param   JDatabaseDriver &$dbo  A database connector object
	 */
	public function __construct(&$dbo = null)
	{
		parent::__construct('#__organizer_pools', 'id', $dbo);
	}

	/**
	 * Set the table column names which are allowed to be null
	 *
	 * @return boolean  true
	 */
	public function check()
	{
		// These can be blank, but non-empty values should be unique.
		$nullColumns = ['alias', 'groupID', 'fieldID', 'lsfID'];
		foreach ($nullColumns as $nullColumn)
		{
			if (!strlen($this->$nullColumn))
			{
				$this->$nullColumn = null;
			}
		}

		return true;
	}
}
