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
 * Models the organizer_groups table.
 */
class Groups extends BaseTable
{
	use Activated, Aliased, Suppressed;

	/**
	 * The id of the category entry referenced.
	 * INT(11) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $categoryID;

	/**
	 * An abbreviated nomenclature for the resource. Currently corresponding to the identifier in Untis scheduling
	 * software.
	 * VARCHAR(60) DEFAULT NULL
	 *
	 * @var string
	 */
	public $code;

	/**
	 * The resource's German name.
	 * VARCHAR(200) NOT NULL
	 *
	 * @var string
	 */
	public $fullName_de;

	/**
	 * The resource's English name.
	 * VARCHAR(200) NOT NULL
	 *
	 * @var string
	 */
	public $fullName_en;

	/**
	 * The id of the grid entry referenced.
	 * INT(11) UNSIGNED DEFAULT 1
	 *
	 * @var int
	 */
	public $gridID;

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
	 * Declares the associated table
	 *
	 * @param   JDatabaseDriver &$dbo  A database connector object
	 */
	public function __construct(&$dbo = null)
	{
		parent::__construct('#__organizer_groups', 'id', $dbo);
	}
}
