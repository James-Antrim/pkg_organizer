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

use Joomla\CMS\Form\Form;
use Organizer\Helpers;

/**
 * Class retrieves the data regarding a filtered set of courses.
 */
class Courses extends ListModel
{
	use Helpers\Filtered;

	protected $defaultOrdering = 'dates';

	protected $filter_fields = ['campusID', 'status', 'termID'];

	/**
	 * @inheritDoc
	 */
	protected function filterFilterForm(Form &$form)
	{
		parent::filterFilterForm($form);

		if ($this->adminContext)
		{
			return;
		}

		$form->removeField('termID', 'filter');

		$params = Helpers\Input::getParams();

		if ($params->get('campusID'))
		{
			$form->removeField('campusID', 'filter');
		}

		if ($params->get('onlyPrepCourses'))
		{
			$form->removeField('search', 'filter');
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getItems(): array
	{
		if (!$items = parent::getItems())
		{
			return [];
		}

		$userID = Helpers\Users::getID();

		foreach ($items as $item)
		{
			$item->participants = count(Helpers\Courses::getParticipantIDs($item->id));
			$item->registered   = Helpers\CourseParticipants::getState($item->id, $userID);
		}

		return $items ?: [];
	}

	/**
	 * @inheritDoc
	 */
	protected function getListQuery()
	{
		$tag   = Helpers\Languages::getTag();
		$query = $this->_db->getQuery(true);
		$query->select("c.*, c.name_$tag AS name, MIN(u.startDate) AS startDate, MAX(u.endDate) AS endDate")
			->from('#__organizer_courses AS c')
			->innerJoin('#__organizer_units AS u ON u.courseID = c.id')
			->innerJoin('#__organizer_instances AS i ON i.unitID = u.id')
			->innerJoin('#__organizer_events AS e ON e.id = i.eventID')
			->group('c.id');

		$direction = $this->state->get('list.direction');

		switch ($this->state->get('list.ordering'))
		{
			case 'name':
				if ($direction === 'DESC')
				{
					$query->order("c.name_$tag DESC");
				}
				else
				{
					$query->order("c.name_$tag ASC");
				}
				break;
			case 'dates':
			default:
				if ($direction === 'DESC')
				{
					$query->order('u.endDate DESC');
				}
				else
				{
					$query->order('u.startDate ASC');
				}
				break;
		}

		$this->setSearchFilter($query, ['c.name_de', 'c.name_en', 'e.name_de', 'e.name_en']);

		if ($this->adminContext)
		{
			$organizationIDs = implode(',', Helpers\Can::scheduleTheseOrganizations());
			$query->where("u.organizationID in ($organizationIDs)");
		}

		if (!$this->adminContext and Helpers\Input::getParams()->get('onlyPrepCourses'))
		{
			$query->where('e.preparatory = 1');
		}
		else
		{
			$this->setValueFilters($query, ['c.termID']);
		}

		if (empty($this->state->get('filter.status')))
		{
			$today = date('Y-m-d');
			$query->where("endDate >= '$today'");
		}

		self::addCampusFilter($query, 'c');

		return $query;
	}

	/**
	 * @inheritDoc
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState($ordering, $direction);

		if (!$this->adminContext)
		{
			$params = Helpers\Input::getParams();

			if ($campusID = $params->get('campusID'))
			{
				$this->state->set('filter.campusID', $campusID);
			}
		}
	}
}
