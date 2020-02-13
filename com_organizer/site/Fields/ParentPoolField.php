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
 * Class creates a select box for superordinate (subject) pool mappings.
 */
class ParentPoolField extends FormField
{
	use Translated;

	/**
	 * Type
	 *
	 * @var    String
	 */
	protected $type = 'ParentPool';

	/**
	 * Returns a select box in which pools can be chosen as a parent node
	 *
	 * @return string  the HTML for the parent pool select box
	 */
	public function getInput()
	{
		$options = $this->getOptions();
		$select  = '<select id="jformparentID" name="jform[parentID][]" multiple="multiple" size="10">';
		$select  .= implode('', $options) . '</select>';

		return $select;
	}

	/**
	 * Gets pool options for a select list. All parameters come from the
	 *
	 * @return array  the options
	 */
	protected function getOptions()
	{
		$resourceID   = Helpers\Input::getID();
		$contextParts = explode('.', $this->form->getName());
		$resourceType = str_replace('edit', '', $contextParts[1]);

		// Initial program ranges are dependant on existing ranges.
		$programRanges = $resourceType === 'pool' ?
			Helpers\Pools::getPrograms($resourceID) : Helpers\Subjects::getPrograms($resourceID);

		return Helpers\Pools::getSuperOrdinateOptions($resourceID, $resourceType, $programRanges);
	}
}
