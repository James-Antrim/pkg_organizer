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
 * Models the organizer_terms table.
 */
class Terms extends BaseTable
{
	use Aliased;

	/**
	 * An abbreviated nomenclature for the resource. Currently corresponding to the identifier in Untis scheduling
	 * software.
	 * VARCHAR(60) DEFAULT NULL
	 *
	 * @var string
	 */
	public $code;

	/**
	 * The end date of the resource.
	 * DATE DEFAULT NULL
	 *
	 * @var string
	 */
	public $endDate;

	/**
	 * The resource's full German name.
	 * VARCHAR(200) DEFAULT ''
	 *
	 * @var string
	 */
	public $fullName_de;

	/**
	 * The resource's full English name.
	 * VARCHAR(200) DEFAULT ''
	 *
	 * @var string
	 */
	public $fullName_en;

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
	 * The start date of the resource.
	 * DATE DEFAULT NULL
	 *
	 * @var string
	 */
	public $startDate;

	/**
	 * Declares the associated table
	 *
	 * @param   JDatabaseDriver &$dbo  A database connector object
	 */
	public function __construct(&$dbo = null)
	{
		parent::__construct('#__organizer_terms', 'id', $dbo);
	}
}
