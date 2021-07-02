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
 * This value type is used to model values that contain human-readable text.
 *
 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.3.11
 */
class Text extends VType
{
	/**
	 * The language tag.
	 *
	 * @var string
	 */
	protected $language;

	protected $type = 'TEXT';

	/**
	 * @var string the text
	 */
	protected $value;

	public function __construct(string $text)
	{
		// Replace single backslash occurences not escaping commas, new lines or semicolons with double backslashes
		$text = preg_replace('/([^\\])\\([^\\;,nN])/', '$1\\\\$2', $text);

		// For whatever reason the rfc does not want colons to be escaped.

		// Replace unescaped commas and semicolons
		$this->value = preg_replace('/([^\\])([,;])/', '$1\\$2', $text);

		$this->language = Languages::getTag();
	}

	/**
	 * Gets the timezone prefixed datetime
	 *
	 * @return string the property value
	 */
	public function value(): string
	{
		return ";LANGUAGE=$this->language:$this->value";
	}
}