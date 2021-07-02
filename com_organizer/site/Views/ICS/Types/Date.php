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
 * This value type is used to model values that contain a calendar date.
 *
 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.3.4
 */
class Date extends VType
{
	protected $type = 'DATE';

	/**
	 * @var string the calendar date
	 */
	protected $value;

	public function __construct(string $value)
	{
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === false)
		{
			$value = date('Y-m-d');
		}

		$this->value = str_replace('-', '', $value);
	}

	/**
	 * @inheritDoc
	 */
	public function value()
	{
		return $this->value;
	}
}