<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Fields;

use Joomla\CMS\Factory;
use Organizer\Helpers;
use stdClass;

/**
 * Class creates a select box for predefined colors.
 */
abstract class ColoredOptionsField extends OptionsField
{
	/**
	 * Type
	 *
	 * @var    String
	 */
	protected $type = 'ColorOptions';

	/**
	 * Returns a select box which contains the colors
	 *
	 * @return string  the HTML for the color select box
	 */
	public function getInput()
	{
		$onChange = empty($this->getAttribute('onchange')) ?
			'' : ' onchange="' . $this->getAttribute('onchange') . '"';
		$html     = '<select name="' . $this->name . '"' . $onChange . '>';
		$options  = $this->getOptions();
		foreach ($options as $option)
		{
			$style    = isset($option->style) ? ' style="' . $option->style . '"' : '';
			$selected = $this->value == $option->value ? ' selected="selected"' : '';
			$html     .= '<option value="' . $option->value . '"' . $selected . $style . '>';
			$html     .= $option->text . '</option>';
		}
		$html .= '</select>';

		return $html;
	}
}
