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
 * Class creates a form field for template selection.
 * @todo rename this and make it generally accessible should this usage occur again.
 */
class TemplatesField extends OptionsField
{
	/**
	 * @var  string
	 */
	protected $type = 'Templates';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions()
	{
		return Helpers\HTML::getTranslatedOptions($this, $this->element);
	}
}
