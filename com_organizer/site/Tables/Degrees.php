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
 * Models the organizer_degrees table.
 */
class Degrees extends BaseTable
{
	use Aliased;
	use Coded;

	/**
	 * The resource's abbreviation.
	 * VARCHAR(45) NOT NULL DEFAULT ''
	 *
	 * @var string
	 */
	public $abbreviation;

	/**
	 * The resource's name.
	 * VARCHAR(255) NOT NULL
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Declares the associated table.
	 */
	public function __construct()
	{
		parent::__construct('#__organizer_degrees');
	}
}
