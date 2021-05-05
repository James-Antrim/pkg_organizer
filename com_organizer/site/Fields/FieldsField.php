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

use Organizer\Helpers;

/**
 * Class creates a form field for building selection.
 */
class FieldsField extends OptionsField
{
	/**
	 * @var  string
	 */
	protected $type = 'Fields';

	/**
	 * Returns a select box where stored buildings can be chosen
	 *
	 * @return array  the available buildings
	 */
	protected function getOptions()
	{
		$defaultOptions = parent::getOptions();
		$options        = Helpers\Fields::getOptions();

		return array_merge($defaultOptions, $options);
	}
}
