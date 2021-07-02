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

use Organizer\Helpers\Languages;

/**
 * This value type is used to model values that contain a uniform resource identifier (URI) type of reference to the
 * property value.
 *
 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.3.13
 */
class Uri extends VType
{

	protected $type = 'URI';

	/**
	 * @var string the uri
	 */
	protected $value;

	public function __construct(string $uri)
	{
		$this->value = parse_url($uri) ? $uri : '';
	}

	/**
	 * Gets the timezone prefixed datetime
	 *
	 * @return string the property value
	 */
	public function value(): string
	{
		return $this->value;
	}
}