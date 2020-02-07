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
 * Models the organizer_buildings table.
 */
class Buildings extends BaseTable
{
	use Addressable;

	/**
	 * The physical address of the resource.
	 * VARCHAR(255) NOT NULL
	 *
	 * @var string
	 */
	public $address;

	/**
	 * The id of the campus entry referenced.
	 * INT(11) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $campusID;

	/**
	 * The GPS coordinates of the resource.
	 * VARCHAR(20) NOT NULL
	 *
	 * @var string
	 */
	public $location;

	/**
	 * The resource's name.
	 * VARCHAR(60) NOT NULL
	 *
	 * @var string
	 */
	public $name;

	/**
	 * The property type. Values: 0 - New/Unknown | 1 - Owned | 2 - Leased/Rented
	 * INT(1) UNSIGNED  NOT NULL DEFAULT 0
	 *
	 * @var int
	 */
	public $propertyType;

	/**
	 * Declares the associated table
	 *
	 * @param   JDatabaseDriver &$dbo  A database connector object
	 */
	public function __construct(&$dbo = null)
	{
		parent::__construct('#__organizer_buildings', 'id', $dbo);
	}

	/**
	 * Set the table column names which are allowed to be null
	 *
	 * @return boolean  true
	 */
	public function check()
	{
		if (!$this->campusID)
		{
			$this->campusID = null;
		}

		return true;
	}
}
