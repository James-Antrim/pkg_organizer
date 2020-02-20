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

use Joomla\CMS\Form\FormField;
use Organizer\Helpers;

/**
 * Class creates text input.
 */
class DateField extends FormField
{
	use Translated;

	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.7.0
	 */
	protected $type = 'Date';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.7.0
	 */
	protected function getInput()
	{
		$empty    = $this->getAttribute('empty', 'true');
		$onchange = $this->getAttribute('onchange', '');

		if ($this->value)
		{
			$value = Helpers\Dates::standardizeDate($this->value);
		}
		else
		{
			$value = $empty === 'false' ? Helpers\Dates::standardizeDate() : '';
		}

		$attributes = [
			$this->autofocus ? 'autofocus' : '',
			$this->class ? "class=\"$this->class\"" : '',
			$this->disabled ? 'disabled' : '',
			"id=\"$this->id\"",
			"name=\"$this->name\"",
			$onchange ? "onChange=\"$onchange\"" : '',
			$this->readonly ? 'readonly' : '',
			$this->required ? 'required aria-required="true"' : '',
			'type="date"',
			'value="' . $value . '"'
		];

		return '<input ' . implode(' ', $attributes) . '/>';
	}
}
