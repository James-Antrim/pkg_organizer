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
 * Class creates a select box for organizations.
 */
class OrganizationFilterField extends OptionsField
{
	/**
	 * @var  string
	 */
	protected $type = 'OrganizationFilter';

	/**
	 * Method to get the field input markup for a generic list.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		$this->adminContext = Helpers\OrganizerHelper::getApplication()->isClient('administrator');
		$onchange           = $this->onchange ? ' onchange="' . $this->onchange . '"' : '';

		// Get the field options.
		$options = (array) $this->getOptions();

		return Helpers\HTML::_(
			'select.genericlist',
			$options,
			$this->name,
			$onchange,
			'value',
			'text',
			$this->value,
			$this->id
		);
	}

	/**
	 * Returns an array of options
	 *
	 * @return array  the organization options
	 */
	protected function getOptions()
	{
		$options       = parent::getOptions();
		$access        = $this->adminContext === self::BACKEND ? $this->getAttribute('access', '') : '';
		$organizations = Helpers\Organizations::getOptions(true, $access);

		return count($organizations) > 1 ? array_merge($options, $organizations) : $organizations;
	}
}
