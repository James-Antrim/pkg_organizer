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
 * This value type is used to model values that specify a precise calendar date and time of day.
 *
 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.3.5
 */
class DateTime extends VType
{
	protected $type = 'DATE-TIME';

	private $tzID;

	/**
	 * @var string the calendar date
	 */
	protected $value;

	public function __construct(string $dateTime)
	{
		$this->tzID = date_default_timezone_get();

		// If the data is invalid here, it will be inconsistend in it's display, but no further error handling.
		if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $dateTime) === false)
		{
			$this->value = date('Ymd') . 'T' . date('His');
		}
		else
		{
			$dateTime    = strtotime($dateTime);
			$this->value = date('Ymd', $dateTime) . 'T' . date('His', $dateTime);
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