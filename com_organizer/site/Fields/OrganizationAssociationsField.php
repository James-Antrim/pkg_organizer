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
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class creates a select box for organizations.
 */
class OrganizationAssociationsField extends OptionsField
{
	/**
	 * @var  string
	 */
	protected $type = 'OrganizationAssociations';

	/**
	 * Retrieves the organization ids associated with the resource.
	 *
	 * @param   string  $resource    the resoure type
	 * @param   int     $resourceID  the resource id
	 *
	 * @return array the ids of the organizations associated with the resource
	 */
	private function getAssociatedOrganizations($resource, $resourceID)
	{
		if ($resource === 'fieldcolor')
		{
			$table = new Tables\FieldColors;

			return ($table->load($resourceID) and !empty($table->organizationID)) ? [$table->organizationID] : [];
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('DISTINCT organizationID')->from('#__organizer_associations')->where("{$resource}ID = $resourceID");
		$dbo->setQuery($query);

		return Helpers\OrganizerHelper::executeQuery('loadColumn', []);
	}

	/**
	 * Retrieves the organization ids authorized for use by the user.
	 *
	 * @param   string  $resource  the resoure type
	 *
	 * @return array the ids of the organizations associated with the resource
	 */
	private function getAuthorizedOrganizations($resource)
	{
		switch ($resource)
		{
			case 'category':
			case 'event':
			case 'group':
				return Helpers\Can::scheduleTheseOrganizations();
			case 'fieldcolor':
			case 'pool':
			case 'program':
			case 'subject':
				return Helpers\Can::documentTheseOrganizations();
			case 'person':
				if (Helpers\Can::manage('persons'))
				{
					return Helpers\Organizations::getIDs();
				}
			default:
				return [];
		}
	}

	/**
	 * Method to get the field input markup for a generic list.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		$contextParts = explode('.', $this->form->getName());
		$disabled     = false;
		$resource     = str_replace('edit', '', $contextParts[1]);
		$resourceID   = Helpers\Input::getID();

		$authorized = $this->getAuthorizedOrganizations($resource);

		if ($associated = $this->getAssociatedOrganizations($resource, $resourceID))
		{
			$this->value = $resource === 'fieldcolor' ? $associated[0] : $associated;

			$assocCount = count($associated);
			$authCount  = count($authorized);
			// The already associated organizations are a
			if (count(array_intersect($authorized, $associated)) === $assocCount and $authCount > $assocCount)
			{
				$displayed = $authorized;
			}
			else
			{
				$displayed = $associated;
				$disabled  = true;
			}
		}
		else
		{
			$displayed = $authorized;
		}

		$options = [];

		foreach ($displayed as $organizationID)
		{
			$shortName = Helpers\Organizations::getShortName($organizationID);
			$options[] = Helpers\HTML::_('select.option', $organizationID, $shortName);
		}

		$attr = '';

		if ($resource !== 'fieldcolor')
		{
			$attr .= ' multiple';
			$this->name = $this->name . '[]';
		}

		$attr .= !empty($this->class) ? ' class="' . $this->class . '"' : '';

		if ($disabled)
		{
			$attr .= ' disabled="disabled"';
			$attr .= ' size="' . count($options) . '"';
		}
		else
		{
			$attr .= ' size="3" required aria-required="true" autofocus';
		}

		return Helpers\HTML::_(
			'select.genericlist',
			$options,
			$this->name,
			trim($attr),
			'value',
			'text',
			$this->value,
			$this->id
		);
	}
}
