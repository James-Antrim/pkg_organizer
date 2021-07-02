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
 * This value type is used to model values that contain a time of day.
 *
 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.3.12
 */
class Time extends VType
{
	protected $type = 'TIME';

	private $tzID;

	/**
	 * @var string the time value as a preformatted string
	 */
	protected $value;

	public function __construct(string $time)
	{
		$this->tzID = date_default_timezone_get();

		// If the data is invalid here, it will be inconsistend in it's display, but no further error handling.
		if (!preg_match('/^\d{2}:\d{2}$/', $time) === false)
		{
			$this->value = date('His');
		}
		else
		{
			$this->value = date('His', strtotime($time));
		}
	}

	/**
	 * Gets the timezone prefixed datetime
	 *
	 * @return string the property value
	 */
	public function value(): string
	{
		return ";$this->tzID:$this->value";
	}
}