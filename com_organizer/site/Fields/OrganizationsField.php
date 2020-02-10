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
use Joomla\CMS\Uri\Uri;
use Organizer\Helpers\Organizations;

/**
 * Class creates a select box for organizations.
 */
class OrganizationsField extends OptionsField
{
	/**
	 * @var  string
	 */
	protected $type = 'Organizations';

	/**
	 * Method to get the field input markup for organization selection.
	 * Use the multiple attribute to enable multiselect.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		// Add custom js script to update other fields like programs
		if (!empty($this->class) and $this->class === 'organizationlist')
		{
			Factory::getDocument()->addScript(Uri::root() . 'components/com_organizer/js/organizationlist.js');
		}

		return parent::getInput();
	}

	/**
	 * Returns an array of options
	 *
	 * @return array  the organization options
	 */
	protected function getOptions()
	{
		$options       = parent::getOptions();
		$organizations = Organizations::getOptions(true, $this->getAttribute('access', ''));

		return array_merge($options, $organizations);
	}
}
