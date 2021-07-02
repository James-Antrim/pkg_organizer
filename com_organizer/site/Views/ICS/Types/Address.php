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
 * This value type is used to model properties that contain a calendar user address.
 *
 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.3.3
 */
class Address extends VType
{
	protected $type = 'CAL-ADDRESS';

	/**
	 * @var string the user's email address
	 */
	protected $value;

	public function __construct(string $address)
	{
		$this->value = $address;
	}

	/**
	 * Gets the timezone prefixed datetime
	 *
	 * @return string the property value
	 */
	public function value(): string
	{
		return "mailto:$this->value";
	}
}