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

use Organizer\Adapters\Database;
use Organizer\Helpers;

/**
 * Class creates a select box for organizations.
 */
class OrganizationAssociationsField extends OptionsField
{
	private $singleAssoc = ['event' => 'Events', 'fieldcolor' => 'FieldColors'];

	/**
	 * @var  string
	 */
	protected $type = 'OrganizationAssociations';

	/**
	 * Retrieves the organization ids associated with the resource.
	 *
	 * @param   string  $resource    the resource type
	 * @param   int     $resourceID  the resource id
	 *
	 * @return array the ids of the organizations associated with the resource
	 */
	private function getAssociatedOrganizations($resource, $resourceID)
	{
		if (array_key_exists($resource, $this->singleAssoc))
		{
			$tableName = 'Organizer\\Tables\\' . $this->singleAssoc[$resource];
			$table     = new $tableName();

			return ($table->load($resourceID) and !empty($table->organizationID)) ? [$table->organizationID] : [];
		}

		$query = Database::getQuery(true);
		$query->select('DISTINCT organizationID')->from('#__organizer_associations')->where("{$resource}ID = $resourceID");
		Database::setQuery($query);

		return Database::loadIntColumn();
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
		$resourceID   = Helpers\Input::getID() ? Helpers\Input::getID() : Helpers\Input::getSelectedID();

		$authorized = $this->getAuthorizedOrganizations($resource);

		if ($associated = $this->getAssociatedOrganizations($resource, $resourceID))
		{
			$this->value = $resource === 'fieldcolor' ? $associated[0] : $associated;

			$assocCount = count($associated);
			$authCount  = count($authorized);

			// The already associated organizations are a subset of the organizations authorized for editing
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

		$displayed = array_flip($displayed);

		foreach (array_keys($displayed) as $organizationID)
		{
			$displayed[$organizationID] = Helpers\Organizations::getShortName($organizationID);
		}

		asort($displayed);

		$options = [];

		foreach ($displayed as $organizationID => $shortName)
		{
			$options[] = Helpers\HTML::_('select.option', $organizationID, $shortName);
		}

		$attr = '';
		$attr .= !empty($this->class) ? ' class="' . $this->class . '"' : '';

		if (array_key_exists($resource, $this->singleAssoc))
		{
			$attr .= ' required aria-required="true" autofocus';
		}
		else
		{
			$this->name = $this->name . '[]';

			if (count($options) > 1)
			{
				$attr .= ' multiple';
			}

			$count = count($options);

			if ($disabled)
			{
				$attr .= ' disabled="disabled"';
				$attr .= ' size="' . count($options) . '"';
			}
			else
			{
				$attr .= $count > 3 ? ' size="10"' : " size=\"$count\"";
				$attr .= ' size="3" required aria-required="true" autofocus';
			}
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
