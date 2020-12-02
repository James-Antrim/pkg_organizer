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
class DisplayValueField extends FormField
{
	/**
	 * @inheritdoc
	 */
	protected $type = 'DisplayValue';

	/**
	 * @inheritDoc
	 */
	protected function getInput()
	{
		return '<span class="display-value">' . $this->value . '</span>';
	}
}
