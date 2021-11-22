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
use Organizer\Helpers\Input;
use Organizer\Helpers\Instances as Helper;
use Organizer\Helpers\Languages;
use Organizer\Tables;

/**
 * Class retrieves information for a filtered set of instances.
 */
class Instances extends ListModel
{
	private const MONDAY = 1, TUESDAY = 2, WEDNESDAY = 3, THURSDAY = 4, FRIDAY = 5, SATURDAY = 6, SUNDAY = 7;

	/**
	 * The conditions used to determine instance relevance.
	 *
	 * @var array
	 */
	public $conditions = [];

	protected $defaultOrdering = 'name';

	protected $filter_fields = [
		'campusID',
		'categoryID',
		'groupID',
		'methodID',
		'organizationID',
		'personID',
		'roomID'
	];

	public $grid;

	public $gridID;

	public $layout;

	public $noDate = false;

	/**
	 * @inheritDoc
	 */
	public function filterFilterForm(Form &$form)
	{
		parent::filterFilterForm($form);

		$params = Input::getParams();
		$layout = $params->get('layout');

		// Layout set in the menu
		if (is_numeric($layout))
		{
			$form->removeField('layout', 'list');
		}

		if ($this->layout === Helper::LIST)
		{
			$form->removeField('gridID', 'list');
		}
		else
		{
			$form->removeField('interval', 'list');
			$form->removeField('limit', 'list');
		}

		if ($this->adminContext)
		{
			if (count(Helpers\Can::scheduleTheseOrganizations()) === 1)
			{
				$form->removeField('organizationID', 'filter');
				unset($this->filter_fields['organizationID']);
			}
		}
		elseif ($params->get('my'))
		{
			$form->removeField('campusID', 'filter');
			$form->removeField('categoryID', 'filter');
			$form->removeField('groupID', 'filter');
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
			if (!Helpers\Users::getID())
			{
				$form->removeField('my', 'list');
			}

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

			if ($params->get('organizationID') or Input::getInt('organizationID'))
			{
				$form->removeField('campusID', 'filter');
				$form->removeField('organizationID', 'filter');
				unset($this->filter_fields[array_search('organizationID', $this->filter_fields)]);
			}
			elseif (Input::getInt('categoryID'))
			{
				$form->removeField('campusID', 'filter');
				$form->removeField('organizationID', 'filter');
				$form->removeField('categoryID', 'filter');
				unset(
					$this->filter_fields[array_search('organizationID', $this->filter_fields)],
					$this->filter_fields[array_search('categoryID', $this->filter_fields)]
				);
			}
			elseif (Input::getInt('groupID'))
			{
				$form->removeField('campusID', 'filter');
				$form->removeField('organizationID', 'filter');
				$form->removeField('categoryID', 'filter');
				$form->removeField('groupID', 'filter');
				unset(
					$this->filter_fields[array_search('organizationID', $this->filter_fields)],
					$this->filter_fields[array_search('categoryID', $this->filter_fields)],
					$this->filter_fields[array_search('groupID', $this->filter_fields)]
				);
			}

			$dow       = $params->get('dow');
			$endDate   = $params->get('endDate');
			$methodIDs = $params->get('methodIDs');
			$methodIDs = array_filter($methodIDs);

			if ($dow or $endDate or $methodIDs)
			{
				$this->noDate = true;
				$form->removeField('date', 'list');
				$form->removeField('interval', 'list');

				if ($methodIDs)
				{
					$form->removeField('methodID', 'filter');
					unset($this->filter_fields[array_search('methodID', $this->filter_fields)]);
				}
			}
		}

		if (!$this->adminContext and $this->mobile)
		{
			$form->removeField('limit', 'list');
		}
	}

	/**
	 * @inheritDoc.
	 */
	public function getItems(): array
	{
		$items = parent::getItems();

		$usedGrids = [];

		foreach ($items as $key => $instance)
		{
			$instance                       = Helper::getInstance($instance->id);
			$usedGrids[$instance['gridID']] = empty($usedGrids[$instance['gridID']]) ? 1 : $usedGrids[$instance['gridID']] + 1;
			Helper::fill($instance, $this->conditions);
			$items[$key] = (object) $instance;
		}

		if ($this->layout === Helper::GRID)
		{
			if (!$gridID = $this->state->get('list.gridID'))
			{
				if ($usedGrids)
				{
					$gridID = array_search(max($usedGrids), $usedGrids);
				}
				else
				{
					$gridID = Helpers\Grids::getDefault();
				}
			}

			$grid = new Tables\Grids();
			$grid->load($gridID);
			$this->grid   = json_decode($grid->grid, true);
			$this->gridID = $gridID;
		}

		return $items;
	}

	/**
	 * @inheritDoc
	 */
	protected function getListQuery(): JDatabaseQuery
	{
		$conditions = $this->conditions;

		$query = Helper::getInstanceQuery($conditions);

		$query->select("DISTINCT i.id")->order('b.date, b.startTime, b.endTime');

		$this->setSearchFilter($query, ['e.name_de', 'e.name_en']);
		$this->setValueFilters($query, ['b.dow', 'i.methodID']);

		if ($this->state->get('filter.campusID'))
		{
			$query->innerJoin('#__organizer_rooms AS r ON r.id = ir.roomID')
				->innerJoin('#__organizer_buildings AS bd ON bd.id = r.buildingID');
			$this->setCampusFilter($query, 'bd');
		}

		return $query;
	}

	/**
	 * Creates a dynamic title for the instances view.
	 *
	 * @return string
	 */
	public function getTitle(): string
	{
		$params = Input::getParams();

		if ($params->get('show_page_heading') and $title = $params->get('page_title'))
		{
			return $title;
		}

		$title  = $this->layout === Helper::GRID ? Languages::_('ORGANIZER_SCHEDULE') : Languages::_("ORGANIZER_INSTANCES");
		$suffix = '';

		if ($my = (int) $this->state->get('list.my'))
		{
			$username = ($user = Helpers\Users::getUser() and $user->username) ? " ($user->username)" : '';
			$title    = $my === Helper::BOOKMARKS ?
				Languages::_("ORGANIZER_MY_INSTANCES") : Languages::_("ORGANIZER_MY_REGISTRATIONS");
			$title    .= $username;
		}
		else
		{
			// Replace the title
			if ($dow = $params->get('dow'))
			{
				switch ($dow)
				{
					case self::MONDAY:
						$title = Languages::_("ORGANIZER_MONDAY_INSTANCES");
						break;
					case self::TUESDAY:
						$title = Languages::_("ORGANIZER_TUESDAY_INSTANCES");
						break;
					case self::WEDNESDAY:
						$title = Languages::_("ORGANIZER_WEDNESDAY_INSTANCES");
						break;
					case self::THURSDAY:
						$title = Languages::_("ORGANIZER_THURSDAY_INSTANCES");
						break;
					case self::FRIDAY:
						$title = Languages::_("ORGANIZER_FRIDAY_INSTANCES");
						break;
					case self::SATURDAY:
						$title = Languages::_("ORGANIZER_SATURDAY_INSTANCES");
						break;
					case self::SUNDAY:
						$title = Languages::_("ORGANIZER_SUNDAY_INSTANCES");
						break;
				}
			}
			elseif ($methodIDs = $params->get('methodIDs') and $methodIDs = array_filter($methodIDs))
			{
				if (count($methodIDs) === 1)
				{
					$title = Helpers\Methods::getPlural($methodIDs[0]);
				}
				else
				{
					$names = [];

					foreach ($methodIDs as $methodID)
					{
						$names[] = Helpers\Methods::getPlural($methodID);
					}

					$lastName = array_pop($names);
					$title    = implode(', ', $names) . " & $lastName";
				}
			}

			// Which resource
			if ($eventID = $this->state->get('filter.eventID'))
			{
				$suffix .= ': ' . Helpers\Events::getName($eventID);
			}
			elseif ($personID = $this->state->get('filter.personID'))
			{
				$suffix .= ': ' . Helpers\Persons::getDefaultName($personID);
			}
			elseif ($groupID = $this->state->get('filter.groupID'))
			{
				$suffix .= ': ' . Helpers\Groups::getFullName($groupID);
			}
			elseif ($categoryID = $this->state->get('filter.categoryID'))
			{
				$suffix .= ': ' . Helpers\Categories::getName($categoryID);
			}
			elseif ($organizationID = $params->get('organizationID'))
			{
				$fullName  = Helpers\Organizations::getFullName($organizationID);
				$shortName = Helpers\Organizations::getShortName($organizationID);
				$name      = ($this->mobile or strlen($fullName) > 50) ? $shortName : $fullName;
				$suffix    .= ': ' . $name;
			}
			elseif ($campusID = $params->get('campusID'))
			{
				$suffix .= ': ' . Languages::_("ORGANIZER_CAMPUS") . ' ' . Helpers\Campuses::getName($campusID);
			}

		}

		return $title . $suffix;
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

		$layout = Helper::LIST;

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
			$filterItems = Input::getFilterItems();
			$listItems   = Input::getListItems();
			$params      = Input::getParams();
			$menuLayout  = $params->get('layout');

			if (is_numeric($menuLayout) and $menuLayout)
			{
				$layout   = (int) $menuLayout;
				$layout   = in_array($layout, [Helper::LIST, Helper::GRID]) ? $layout : Helper::LIST;
				$interval = $this->mobile ? 'day' : 'week';
				$listItems->set('interval', $interval);
				$this->state->set('list.interval', $interval);
			}
			else
			{
				$layout = (int) $listItems->get('layout', Helper::LIST);

				if ($interval = Input::getString('interval'))
				{
					$listItems->set('interval', $interval);
					$this->state->set('list.interval', $interval);
				}
			}

			$this->layout = $layout;
			$this->state->set('list.layout', $layout);

			if ($my = $listItems->get('my', Input::getInt('my', $params->get('my'))))
			{
				$this->state->set('list.my', $my);
			}

			if ($campusID = $params->get('campusID'))
			{
				$filterItems->set('campusID', $campusID);
				$this->state->set('filter.campusID', $campusID);
			}

			$organizationID = Input::getInt('organizationID');
			$categoryID     = Input::getInt('categoryID');
			$groupID        = Input::getInt('groupID');

			if ($organizationID = $params->get('organizationID', $organizationID))
			{
				$filterItems->set('organizationID', $organizationID);
				$this->state->set('filter.organizationID', $organizationID);

				if ($categoryID)
				{
					$filterItems->set('categoryID', $categoryID);
					$this->state->set('filter.categoryID', $categoryID);
				}

				if ($groupID)
				{
					$filterItems->set('groupID', $groupID);
					$this->state->set('filter.groupID', $groupID);
				}
			}
			elseif ($categoryID)
			{
				$filterItems->set('categoryID', $categoryID);
				$this->state->set('filter.categoryID', $categoryID);

				$organizationID = Helpers\Categories::getOrganizationIDs($categoryID)[0];
				$filterItems->set('organizationID', $organizationID);
				$this->state->set('filter.organizationID', $organizationID);

				if ($groupID)
				{
					$filterItems->set('groupID', $groupID);
					$this->state->set('filter.groupID', $groupID);
				}
			}
			elseif ($groupID)
			{
				$filterItems->set('groupID', $groupID);
				$this->state->set('filter.groupID', $groupID);

				$categoryID = Helpers\Groups::getCategoryID($groupID);
				$filterItems->set('categoryID', $categoryID);
				$this->state->set('filter.categoryID', $categoryID);

				$organizationID = Helpers\Categories::getOrganizationIDs($categoryID)[0];
				$filterItems->set('organizationID', $organizationID);
				$this->state->set('filter.organizationID', $organizationID);
			}

			if ($eventID = Input::getInt('eventID'))
			{
				$this->state->set('filter.eventID', $eventID);
			}
			else
			{
				$this->state->set('filter.eventID', 0);
			}

			if ($personID = Input::getInt('personID'))
			{
				$filterItems->set('roomID', $personID);
				$this->state->set('filter.personID', $personID);
			}

			if ($roomID = Input::getInt('roomID'))
			{
				$filterItems->set('roomID', $roomID);
				$this->state->set('filter.roomID', $roomID);
			}

			if ($date = Input::getString('date'))
			{
				$listItems->set('date', $date);
				$this->state->set('list.date', $date);
			}

			$dow       = $params->get('dow');
			$endDate   = $params->get('endDate');
			$methodIDs = Input::getIntCollection('methodID');
			$methodIDs = $params->get('methodIDs', $methodIDs);
			$methodIDs = array_filter($methodIDs);
			$startDate = $params->get('startDate');

			if ($dow or $endDate or $methodIDs)
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

				if ($methodIDs)
				{
					$filterItems->set('methodID', $methodIDs);
					$this->state->set('filter.methodID', $methodIDs);
				}
			}
		}

		if ($layout === Helper::GRID or ($format = Input::getCMD('format') and $format !== 'html'))
		{
			$this->state->set('list.limit', 0);
		}

		$this->conditions = $this->setConditions();
	}

	/**
	 * Builds the array of parameters used for lesson retrieval.
	 *
	 * @return array the parameters used to retrieve lessons.
	 */
	private function setConditions(): array
	{
		$conditions          = [];
		$conditions['date']  = Helpers\Dates::standardizeDate($this->state->get('list.date', date('Y-m-d')));
		$conditions['delta'] = date('Y-m-d', strtotime('-14 days'));
		$conditions['my']    = $this->state->get('list.my');

		switch (Input::getCMD('format'))
		{
			case 'ics':
				$conditions['interval'] = 'quarter';
				$conditions['status']   = 1;
				break;
			case 'json':
				$interval               = $this->state->get('list.interval', 'week');
				$intervals              = ['day', 'half', 'month', 'quarter', 'term', 'week'];
				$conditions['interval'] = in_array($interval, $intervals) ? $interval : 'week';
				$conditions['status']   = $this->state->get('filter.status', 1);
				break;
			case 'pdf':
				$interval               = $this->state->get('list.interval', 'week');
				$intervals              = ['month', 'quarter', 'term', 'week'];
				$conditions['interval'] = in_array($interval, $intervals) ? $interval : 'week';
				$conditions['status']   = 1;
				break;
			case 'html':
			case 'xls':
			default:
				$default                = $this->mobile ? 'day' : 'week';
				$interval               = $this->state->get('list.interval');
				$intervals              = ['day', 'month', 'quarter', 'term', 'week'];
				$conditions['interval'] = in_array($interval, $intervals) ? $interval : $default;
				$conditions['status']   = $this->state->get('filter.status', 1);
				break;
		}

		// Reliant on date and interval properties
		Helper::setDates($conditions);

		if ($endDate = $this->state->get('list.endDate'))
		{
			$conditions['endDate'] = $endDate;
		}

		if ($categoryID = $this->state->get('filter.categoryID'))
		{
			$conditions['categoryIDs'] = [$categoryID];
		}

		if ($groupID = $this->state->get('filter.groupID'))
		{
			$conditions['groupIDs'] = [$groupID];
		}

		if ($organizationID = $this->state->get('filter.organizationID'))
		{
			$conditions['organizationIDs'] = [$organizationID];

			Helper::setPublishingAccess($conditions);
		}
		else
		{
			$conditions['showUnpublished'] = Helpers\Can::administrate();
		}

		if ($personID = (int) $this->state->get('filter.personID'))
		{
			$personIDs = [$personID];
			Helper::filterPersonIDs($personIDs, Helpers\Users::getID());

			if ($personIDs)
			{
				$conditions['personIDs'] = $personIDs;
			}
			else
			{
				Helpers\OrganizerHelper::error(403);
			}
		}

		if ($roomID = $this->state->get('filter.roomID'))
		{
			$conditions['roomIDs'] = [$roomID];
		}

		return $conditions;
	}
}