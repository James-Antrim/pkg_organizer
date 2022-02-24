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

use Joomla\CMS\Form\FormHelper;

FormHelper::loadFieldClass('subform');

/**
 * Class loads multiple/repeatable Instance Resources from database and make it possible to advance them.
 */
class InstanceResourcesField extends \JFormFieldSubform
{
	/**
	 * Type
	 *
	 * @var    String
	 */
	protected $type = 'InstanceResources';

	/**
	 * Method to get the multiple field input of the loaded Resources in Instances Section
	 *
	 * @return string  The field input markup.
	 */
	protected function getInput()
	{
		$this->value = isset($this->value) ? $this->value : [];

		return parent::getInput();
	}
}
