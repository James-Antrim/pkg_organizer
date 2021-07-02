<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\ICS\Types;

/**
 * Provide a grouping of component properties that describe a component.
 */
abstract class VType
{
	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var mixed literally anything
	 */
	protected $value;

	/**
	 * Gets the text representing the value of the property.
	 *
	 * @return mixed the property value
	 */
	public function value()
	{
		return $this->value;
	}

}