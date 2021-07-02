<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\ICS\Components;


use Organizer\Views\ICS\Constants;

/**
 * Provide a grouping of component properties that describe a component.
 */
abstract class VComponent
{
	/**
	 * @param   array  $ics
	 *
	 * @return mixed
	 */
	abstract public function fill(array &$ics);
}