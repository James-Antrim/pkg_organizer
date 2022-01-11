<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;

use Joomla\CMS\HTML\HTMLHelper;
use Organizer\Fields\FieldsField;
use Organizer\Views\HTML\BaseView;
use SimpleXMLElement;

/**
 * Class provides generalized functions useful for several component files.
 */
class HTML extends HTMLHelper
{
	/**
	 * Creates a dynamically translated label.
	 *
	 * @param   BaseView  $view       the view this method is applied to
	 * @param   string    $inputName  the name of the form field whose label should be generated
	 *
	 * @return string the HMTL for the field label
	 */
	public static function getLabel(BaseView $view, string $inputName): string
	{
		$title  = Languages::_($view->form->getField($inputName)->title);
		$tip    = Languages::_($view->form->getField($inputName)->description);
		$return = '<label id="jform_' . $inputName . '-lbl" for="jform_' . $inputName . '" class="hasPopover"';
		$return .= 'data-content="' . $tip . '" data-original-title="' . $title . '">' . $title . '</label>';

		return $return;
	}

	/**
	 * Creates an array of option objects from an array.
	 *
	 * @param   array  $array
	 *
	 * @return array the HMTL for the field label
	 */
	public static function getOptions(array $array): array
	{
		$options = [];
		foreach ($array as $key => $item)
		{
			if (is_object($item))
			{
				$item = (array) $item;
			}

			if (is_array($item))
			{
				if (array_key_exists('text', $item) and array_key_exists('value', $item))
				{
					$text  = $item['text'];
					$value = $item['value'];
				}
				else
				{
					$text  = reset($item);
					$value = end($item);
				}
			}
			else
			{
				$text  = (string) $item;
				$value = $key;
			}

			$options[] = HTML::_('select.option', $value, $text);
		}

		return $options;
	}

	/**
	 * Gets an array of dynamically translated default options.
	 *
	 * @param   FieldsField       $field    the field object.
	 * @param   SimpleXMLElement  $element  the field's xml signature. passed separately to get around its protected status.
	 *
	 * @return array the default options.
	 */
	public static function getTranslatedOptions(FieldsField $field, SimpleXMLElement $element): array
	{
		$options = [];

		foreach ($element->xpath('option') as $option)
		{
			$value = (string) $option['value'];
			$text  = trim((string) $option) != '' ? trim((string) $option) : $value;

			$disabled = (string) $option['disabled'];
			$disabled = ($disabled == 'true' || $disabled == 'disabled' || $disabled == '1');
			$disabled = $disabled || ($field->readonly && $value != $field->value);

			$checked = (string) $option['checked'];
			$checked = ($checked == 'true' || $checked == 'checked' || $checked == '1');

			$selected = (string) $option['selected'];
			$selected = ($selected == 'true' || $selected == 'selected' || $selected == '1');

			$tmp = [
				'value'    => $value,
				'text'     => Languages::_($text),
				'disable'  => $disabled,
				'class'    => (string) $option['class'],
				'selected' => ($checked || $selected),
				'checked'  => ($checked || $selected),
			];

			$options[] = $tmp;
		}

		return $options;
	}

	/**
	 * Creates the HTML string for an icon.
	 *
	 * @param   string  $name  the name of the icon class
	 * @param   string  $tip   text to be used as a tooltip
	 * @param   bool    $aria  true if the screen reader should ignore
	 *
	 * @return string
	 */
	public static function icon(string $name, string $tip = '', bool $aria = false): string
	{
		$aria  = $aria ? 'aria-hidden="true"' : '';
		$class = "class=\"icon-$name\"";
		$title = '';

		if ($tip)
		{
			$class .= ' hasTooltip';
			$title = "title=\"$tip\"";
		}

		return "<span $aria $class $title></span>";
	}

	/**
	 * Translates an associative array of attributes into a string suitable for use in HTML.
	 *
	 * @param   array  $array  the element attributes
	 *
	 * @return string the HTML string containing the attributes
	 */
	public static function implodeAttributes(array $array): string
	{
		$attributes = [];
		foreach ($array as $key => $value)
		{
			$attributes[] = "$key=\"$value\"";
		}

		return implode(' ', $attributes);
	}

	/**
	 * Creates a select box
	 *
	 * @param   mixed   $options     a set of keys and values
	 * @param   string  $name        the name of the element
	 * @param   mixed   $attributes  optional attributes: object, array, or string in the form key => value(,)+
	 * @param   mixed   $selected    optional selected items
	 * @param   bool    $jform       whether the element will be wrapped by a 'jform' element
	 *
	 * @return string  the html output for the select box
	 */
	public static function selectBox($options, string $name, $attributes = [], $selected = null, bool $jform = false): string
	{
		$isMultiple = (!empty($attributes['multiple']) and $attributes['multiple'] == 'multiple');
		$multiple   = $isMultiple ? '[]' : '';

		$name = $jform ? "jform[$name]$multiple" : "$name$multiple";

		return self::_('select.genericlist', $options, $name, $attributes, 'value', 'text', $selected);
	}

	/**
	 * Provides a simplified interface for sortable headers
	 *
	 * @param   string  $constant   the unique portion of the text constant
	 * @param   string  $column     the column name when sorting by this column
	 * @param   string  $direction  the direction in which to sort
	 * @param   string  $ordering   the column name of the column currently being used for sorting
	 *
	 * @return mixed
	 */
	public static function sort(string $constant, string $column, string $direction, string $ordering)
	{
		$text = Languages::_("ORGANIZER_$constant");

		return self::_('searchtools.sort', $text, $column, $direction, $ordering);
	}
}
