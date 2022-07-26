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
use stdClass;

/**
 * Class creates a select box for superordinate pool resources.
 */
class SuperOrdinatesField extends FormField
{
	use Translated;

	/**
	 * Type
	 *
	 * @var    String
	 */
	protected $type = 'SuperOrdinates';

	/**
	 * Returns a select box in which resources can be chosen as a super ordinates
	 *
	 * @return string  the HTML for the super ordinate resources select box
	 */
	public function getInput(): string
	{
		$options = $this->getOptions();
		$select  = '<select id="superordinates" name="jform[superordinates][]" multiple="multiple" size="10">';
		$select  .= implode('', $options) . '</select>';

		return $select;
	}

	/**
	 * Gets pool options for a select list. All parameters come from the
	 *
	 * @return stdClass[]  the options
	 */
	protected function getOptions(): array
	{
		$resourceID   = Helpers\Input::getID();
		$contextParts = explode('.', $this->form->getName());
		$resourceType = str_replace('edit', '', $contextParts[1]);

		// Initial program ranges are dependant on existing ranges.
		$programRanges = $resourceType === 'pool' ?
			Helpers\Pools::getPrograms($resourceID) : Helpers\Subjects::getPrograms($resourceID);

		return Helpers\Pools::getSuperOptions($resourceID, $resourceType, $programRanges);
	}
}
