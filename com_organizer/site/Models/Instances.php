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
		'status'
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

		if ($this->adminContext)
		{
			if (count(Helpers\Can::scheduleTheseOrganizations()) === 1)
			{
				$form->removeField('organizationID', 'filter');
				unset($this->filter_fields['organizationID']);
			}
		}
		elseif ($this->state->get('filter.my'))
		{
			$form->removeField('campusID', 'filter');
			$form->removeField('categoryID', 'filter');
			$form->removeField('date', 'list');
			$form->removeField('groupID', 'filter');
			$form->removeField('interval', 'list');
			$form->removeField('methodID', 'filter');
			$form->removeField('organizationID', 'filter');
			$form->removeField('personID', 'filter');
			$form->removeField('roomID', 'filter');
			$form->removeField('search', 'filter');
			$form->removeField('status', 'filter');
			$this->filter_fields = [];
		}
		else
		{
			$params = Helpers\Input::getParams();
			if ($params->get('campusID'))
			{
				$form->removeField('campusID', 'filter');
				unset($this->filter_fields[array_search('campusID', $this->filter_fields)]);
			}

			if ($this->state->get('filter.eventID'))
			{
				$form->removeField('campusID', 'filter');
				$form->removeField('categoryID', 'filter');
				$form->removeField('organizationID', 'filter');
				$form->removeField('roomID', 'filter');
			}

			if ($params->get('organizationID'))
			{
				$form->removeField('campusID', 'filter');
				$form->removeField('organizationID', 'filter');
				unset($this->filter_fields[array_search('organizationID', $this->filter_fields)]);
			}

			$dow      = $params->get('dow');
			$endDate  = $params->get('endDate');
			$methodID = $params->get('methodID');

			if ($dow or $endDate or $methodID)
			{
				$form->removeField('date', 'list');
				$form->removeField('interval', 'list');

				if ($methodID)
				{
					$form->removeField('methodID', 'filter');
					unset($this->filter_fields[array_search('methodID', $this->filter_fields)]);
				}
			}
		}
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
			Helpers\Instances::setSubject($instance, $this->conditions);
			Helpers\Instances::setBooking($instance);

			$items[$key] = (object) $instance;
		}

		return $items;
	}

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$conditions = $this->conditions;

		$conditions['isEventsRequired'] = true;

		$query = Helpers\Instances::getInstanceQuery($conditions);

		$query->select("DISTINCT i.id")
			->order('b.date, b.startTime, b.endTime');

		if ($conditions['my'])
		{
			$date = date('Y-m-d');
			$now  = date('H:i:s');
			$query->where("(b.date > '$date' OR (b.date = '$date' AND b.endTime > '$now'))");
		}
		else
		{
			$query->where("b.date BETWEEN '{$conditions['startDate']}' AND '{$conditions['endDate']}'");
		}

		$this->setSearchFilter($query, ['e.name_de', 'e.name_en']);
		$this->setValueFilters($query, ['b.dow', 'i.methodID']);

		if ($this->state->get('filter.campusID'))
		{
			$query->innerJoin('#__organizer_rooms AS r ON r.id = ir.roomID')
				->innerJoin('#__organizer_buildings AS bd ON bd.id = r.buildingID');
			$this->setCampusFilter($query, 'bd');
		}

		if ($this->state->get('filter.categoryID'))
		{
			$query->innerJoin('#__organizer_groups AS g ON g.id = ig.groupID');
			$this->setValueFilters($query, ['g.categoryID',]);
		}

		if ($this->state->get('filter.eventID'))
		{
			$this->setValueFilters($query, ['i.eventID',]);
		}

		if ($this->state->get('filter.organizationID'))
		{
			$query->innerJoin('#__organizer_associations AS ag ON ag.groupID = ig.groupID');
			$this->setValueFilters($query, ['ag.organizationID']);
		}

		return $query;
	}

	/**
	 * @inheritdoc
	 */
	public function getTotal($idColumn = null)
	{
		return parent::getTotal('i.id');
	}

	/**
	 * @inheritdoc
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState($ordering, $direction);

		if ($this->adminContext)
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
			$listItems   = Helpers\Input::getListItems();
			$params      = Helpers\Input::getParams();

			if (Helpers\Input::getInt('my', $params->get('my', 0)))
			{
				$this->state->set('filter.my', 1);
			}

			if ($this->state->get('filter.my'))
			{
				$date = date('Y-m-d');
				$listItems->set('date', $date);
				$this->state->set('list.date', $date);
				$listItems->set('interval', 'quarter');
				$this->state->set('list.interval', 'quarter');
			}
			else
			{
				if ($campusID = $params->get('campusID'))
				{
					$filterItems->set('campusID', $campusID);
					$this->state->set('filter.campusID', $campusID);
				}

				$organizationID = Helpers\Input::getInt('organizationID');
				if ($organizationID = $params->get('organizationID', $organizationID))
				{
					$filterItems->set('organizationID', $organizationID);
					$this->state->set('filter.organizationID', $organizationID);
				}
				elseif ($categoryID = Helpers\Input::getInt('categoryID'))
				{
					$filterItems->set('categoryID', $categoryID);
					$this->state->set('filter.categoryID', $categoryID);

					$organizationID = Helpers\Categories::getOrganizationIDs($categoryID)[0];
					$filterItems->set('organizationID', $organizationID);
					$this->state->set('filter.organizationID', $organizationID);
				}

				if ($eventID = Helpers\Input::getInt('eventID'))
				{
					$this->state->set('filter.eventID', $eventID);
				}
				else
				{
					$this->state->set('filter.eventID', 0);
				}

				$dow       = $params->get('dow');
				$endDate   = $params->get('endDate');
				$methodID  = $params->get('methodID');
				$startDate = $params->get('startDate');

				if ($dow or $endDate or $methodID)
				{
					$defaultDate = date('Y-m-d');
					$date        = ($startDate and $startDate > $defaultDate) ? $startDate : $defaultDate;

					$listItems->set('date', $date);
					$this->state->set('list.date', $date);

					if ($endDate)
					{
						$listItems->set('interval', 'day');
						$this->state->set('list.interval', 'day');
						$this->state->set('list.endDate', $endDate);
					}
					else
					{
						$listItems->set('interval', 'quarter');
						$this->state->set('list.interval', 'quarter');
					}

					if ($dow)
					{
						$filterItems->set('dow', $dow);
						$this->state->set('filter.dow', $dow);
					}

					if ($methodID)
					{
						$filterItems->set('methodID', $methodID);
						$this->state->set('filter.methodID', $methodID);
					}
				}
			}
		}

		$this->conditions = $this->setConditions();
	}

	/**
	 * Builds the array of parameters used for lesson retrieval.
	 *
	 * @return array the parameters used to retrieve lessons.
	 */
	private function setConditions()
	{
		$interval  = $this->state->get('list.interval', 'day');
		$intervals = ['day', 'half', 'month', 'quarter', 'term', 'week'];

		$conditions['date']       = Helpers\Dates::standardizeDate($this->state->get('list.date', date('Y-m-d')));
		$conditions['delta']      = date('Y-m-d', strtotime('-14 days'));
		$conditions['interval']   = in_array($interval, $intervals) ? $interval : 'week';
		$conditions['my']         = $this->state->get('filter.my');
		$conditions['mySchedule'] = false;
		$conditions['status']     = $this->state->get('filter.status', '');

		// Reliant on date and interval properties
		Helpers\Instances::setDates($conditions);

		$this->state->set('list.date', $conditions['startDate']);

		if ($endDate = $this->state->get('list.endDate'))
		{
			$conditions['endDate'] = $endDate;
		}

		if ($groupID = $this->state->get('filter.groupID'))
		{
			$conditions['groupIDs'] = [$groupID];
		}

		if ($organizationID = $this->state->get('filter.organizationID'))
		{
			$conditions['organizationIDs'] = [$organizationID];

			Helpers\Instances::setOrganizationalPublishing($conditions);
		}
		else
		{
			$conditions['showUnpublished'] = Helpers\Can::administrate();
		}

		if ($personID = $this->state->get('filter.personID'))
		{
			$personIDs = [$personID];
			Helpers\Instances::filterPersonIDs($personIDs, Helpers\Users::getID());

			if (!empty($personIDs))
			{
				$conditions['personIDs'] = $personIDs;
			}
		}

		if ($roomID = $this->state->get('filter.roomID'))
		{
			$conditions['roomIDs'] = [$roomID];
		}

		return $conditions;
	}
}