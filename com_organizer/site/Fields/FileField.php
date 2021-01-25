<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Fields;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Utility\Utility;
use Organizer\Helpers\HTML;
use Organizer\Helpers\Languages;
use SimpleXMLElement;

/**
 * Class creates file input. Joomla does not load language files from the administrator context for standard fields.
 */
class FileField extends FormField
{
	use Translated;

	protected $type = 'File';

	protected $accept;

	/**
	 * @inheritDoc
	 */
	public function __get($name): string
	{
		switch ($name)
		{
			case 'accept':
				return $this->accept;
		}

		return parent::__get($name);
	}

	/**
	 * @inheritDoc
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'accept':
				$this->accept = (string) $value;
				break;

			default:
				parent::__set($name, $value);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null): bool
	{
		$return = parent::setup($element, $value, $group);

		if ($return)
		{
			$this->accept = (string) $this->element['accept'];
		}

		return $return;
	}

	/**
	 * Method to get the field input markup for the file field.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		$attributes     = [
			$this->accept ? 'accept="' . $this->accept . '"' : '',
			$this->class ? 'class="' . $this->class . '"' : '',
			"id=\"$this->id\"",
			$this->multiple ? 'multiple' : '',
			"name=\"$this->name\"",
			$this->required ? 'required aria-required="true"' : '',
			$this->size ? 'size="' . (int) $this->size . '"' : '',
			'type="file"',
		];
		$attributes     = array_filter($attributes);
		$maxSize        = HTML::_('number.bytes', Utility::getMaxUploadSize());
		$uploadSizeText = Languages::sprintf('JGLOBAL_MAXIMUM_UPLOAD_SIZE_LIMIT', $maxSize);

		return '<input ' . implode(' ', $attributes) . '/><br>' . $uploadSizeText;
	}
}
