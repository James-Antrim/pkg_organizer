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
 * This value type is used to model properties that contain either a "TRUE" or "FALSE" Boolean value.
 *
 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.3.2
 */
class Boolean extends VType
{
	protected $type = 'BOOLEAN';

	/**
	 * @var string (TRUE|FALSE)
	 */
	protected $value;

	public function __construct(bool $value)
	{
		$this->value = $value ? 'TRUE' : 'FALSE';
	}

	/**
	 * @inheritDoc
	 */
	public function value()
	{
		return $this->value;
	}
}