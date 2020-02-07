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
 * Resources which can be reached over a URL are addressable.
 */
trait Aliased
{
	/**
	 * The alias used to reference the resource in an URL
	 * VARCHAR(255) DEFAULT ''
	 *
	 * @var string
	 */
	public $alias;
}