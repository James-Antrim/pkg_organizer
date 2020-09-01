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
 * Models the organizer_blocks table.
 */
class Roles extends BaseTable
{
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
	 * An abbreviated nomenclature for the resource.
	 * VARCHAR(25) NOT NULL
	 *
	 * @var string
	 */
	public $code;

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
	 * The resource's German plural.
	 * VARCHAR(150) NOT NULL
	 *
	 * @var string
	 */
	public $plural_de;

	/**
	 * The resource's English plural.
	 * VARCHAR(150) NOT NULL
	 *
	 * @var string
	 */
	public $plural_en;

	/**
	 * Declares the associated table
	 *
	 * @param   JDatabaseDriver  $dbo  A database connector object
	 */
	public function __construct($dbo = null)
	{
		parent::__construct('#__organizer_roles', 'id', $dbo);
	}
}