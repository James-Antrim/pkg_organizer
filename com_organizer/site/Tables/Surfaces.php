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
 * Models the organizer_colors table.
 */
class Surfaces extends BaseTable
{
	use Coded;

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
	 * The id of the Net Room Surface / Netto-Raumfläche referenced by the specific code
	 * INT(2) UNSIGNED NOT NULL
	 *
	 * @var int
	 */
	public $typeID;

	/**
	 * Declares the associated table.
	 */
	public function __construct()
	{
		parent::__construct('#__organizer_surfacetypes');
	}
}
