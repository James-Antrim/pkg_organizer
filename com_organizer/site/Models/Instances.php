<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Exception;
use JDatabaseQuery;
use Joomla\CMS\Form\Form;
use Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of instances.
 */
class Instances extends ListModel
{
	private $conditions = [];

	protected $filter_fields = [
		'campusID',
		'categoryID',
		'groupID',
		'methodID',
		'organizationID',
		'personID',
		'roomID',
		'status',
		'dow'
	];

	protected $defaultOrdering = 'name';

	/**
	 * Filters out form inputs which should not be displayed due to menu settings.
	 *
	 * @param   Form  $form  the form to be filtered
	 *
	 * @return void modifies $form
	 */
	public function filterFilterForm(&$form)
	{
		parent::filterFilterForm($form);

		if ($this->clientContext === self::BACKEND)
		{
			if (count(Helpers\Can::scheduleTheseOrganizations()) === 1)
			{
				$form->removeField('organizationID', 'filter');
				unset($this->filter_fields['organizationID']);
			}
		}
		else
		{
			$params = Helpers\Input::getParams();

			if (!$groups = $form->getField('groupID', 'filter')->options or count($groups) === 1)
			{
				$form->removeField('groupID', 'filter');
				unset($this->filter_fields[array_search('groupID', $this->filter_fields)]);
			}

			if (!$persons = $form->getField('personID', 'filter')->options or count($persons) === 1)
			{
				$form->removeField('personID', 'filter');
				unset($this->filter_fields[array_search('personID', $this->filter_fields)]);
			}

			if ($params->get('campusID'))
			{
				$form->removeField('campusID', 'filter');
				unset($this->filter_fields[array_search('campusID', $this->filter_fields)]);
			}

			if ($params->get('organizationID'))
			{
				$form->removeField('campusID', 'filter');
				$form->removeField('organizationID', 'filter');
				unset($this->filter_fields[array_search('organizationID', $this->filter_fields)]);
			}

			if ($params->get('methodID', 'filter'))
			{
				$form->removeField('methodID', 'filter');
				unset($this->filter_fields[array_search('methodID', $this->filter_fields)]);
			}
		}

		return;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  array|bool  An array of data items on success, false on failure.
	 */
	public function getItems()
	{
		$items = parent::getItems();

		foreach ($items as $key => $instance)
		{
			$instance = Helpers\Instances::getInstance($instance->id);
			Helpers\Instances::setPersons($instance, $this->conditions);

			$organizationIDs = [];
			foreach ($instance['resources'] as $personID => $resources)
			{
				foreach ($resources['groups'] as $groupID => $group)
				{
					if ($group['status'] !== 'removed')
					{
						$organizationIDs = array_merge($organizationIDs, Helpers\Groups::getOrganizationIDs($groupID));
					}
				}
			}

			if ($organizationIDs = array_unique($organizationIDs))
			{
				if (count($organizationIDs) > 1)
				{
					$instance['organization'] = Helpers\Languages::_('ORGANIZER_MULTIPLE_ORGANIZATIONS');
				}
				else
				{
					$instance['organization']   = Helpers\Organizations::getShortName($organizationIDs[0]);
					$instance['organizationID'] = $organizationIDs[0];
				}
			}

			Helpers\Instances::setSubject($instance, $this->conditions);

			$items[$key] = (object) $instance;
		}

		return $items;
	}

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 * @throws Exception
	 */
	protected function getListQuery()
	{
		$conditions = $this->conditions;

		$conditions['isEventsRequired'] = true;

		$query = Helpers\Instances::getInstanceQuery($conditions);

		$query->select("DISTINCT i.id")
			->where("b.date BETWEEN '{$conditions['startDate']} 00:00:00' AND '{$conditions['endDate']} 23:59:59'")
			->order('b.date, b.startTime, b.endTime');

		$this->setSearchFilter($query, ['e.name_de', 'e.name_en']);
		$this->setValueFilters($query, ['b.dow', 'i.methodID']);

		$filters = Helpers\Input::getFilterItems();

		if ($filters->get('campusID'))
		{
			$query->innerJoin('#__organizer_rooms AS r ON r.id = ir.roomID')
				->innerJoin('#__organizer_buildings AS bd ON bd.id = r.buildingID');
			$this->setCampusFilter($query, 'bd');
		}

		if ($organizationID = $filters->get('organizationID'))
		{
			$query->innerJoin('#__organizer_associations AS ag ON ag.groupID = ig.groupID');
			$this->setValueFilters($query, ['ag.organizationID']);
		}

		if ($categoryID = $filters->get('categoryID'))
		{
			$query->innerJoin('#__organizer_groups AS g ON g.id = ig.groupID');
			$this->setValueFilters($query, ['g.categoryID',]);
		}

		return $query;
	}

	/**
	 * Method to get the total number of items for the data set.
	 *
	 * @param   string  $idColumn  the main id column of the list query
	 *
	 * @return integer  The total number of items available in the data set.
	 */
	public function getTotal($idColumn = null)
	{
		return parent::getTotal('i.id');
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return void populates state properties
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState($ordering, $direction);

		if ($this->clientContext === self::BACKEND)
		{
			$authorized = Helpers\Can::scheduleTheseOrganizations();
			if (count($authorized) === 1)
			{
				$organizationID = $authorized[0];
				$this->state->set('filter.organizationID', $organizationID);
			}
		}
		else
		{
			$filterItems = Helpers\Input::getFilterItems();
			$params      = Helpers\Input::getParams();

			if ($campusID = $params->get('campusID'))
			{
				$filterItems->set('campusID', $campusID);
				$this->state->set('filter.campusID', $campusID);
			}

			if ($organizationID = $params->get('organizationID'))
			{
				$filterItems->set('organizationID', $organizationID);
				$this->state->set('filter.organizationID', $organizationID);
			}

			if ($methodID = $params->get('methodID'))
			{
				$filterItems->set('methodID', $methodID);
				$this->state->set('filter.methodID', $methodID);
			}

			if ($dow = $params->get('dow'))
			{
				$filterItems->set('dow', $dow);
				$this->state->set('filter.dow', $dow);
			}
		}

		$this->conditions = Helpers\Instances::getConditions();
		$this->state->set('list.date', $this->conditions['startDate']);
	}
}