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
 * Retrieves lesson and event data for a filtered set of rooms.
 */
class RoomOverview extends ListModel
{
	const DAY = 1, WEEK = 2;

	protected $defaultLimit = 10;

	protected $defaultOrdering = 'r.name';

	protected $filter_fields = ['campusID', 'buildingID', 'capacity', 'roomtypeID'];

	/**
	 * Filters out form inputs which should not be displayed due to menu settings.
	 *
	 * @param   Form  $form  the form to be filtered
	 *
	 * @return void modifies $form
	 */
	protected function filterFilterForm(&$form)
	{
		parent::filterFilterForm($form);

		$params = Helpers\Input::getParams();

		if ($params->get('campusID', 0))
		{
			$form->removeField('campusID', 'filter');
		}
	}

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$tag   = Helpers\Languages::getTag();
		$query = $this->_db->getQuery(true);

		$query->select('r.id, r.name AS name, r.capacity')
			->select("t.id AS roomtypeID, t.name_$tag AS typeName, t.description_$tag AS typeDesc")
			->from('#__organizer_rooms AS r')
			->leftJoin('#__organizer_roomtypes AS t ON t.id = r.roomtypeID')
			->leftJoin('#__organizer_buildings AS b ON b.id = r.buildingID');

		// Only display public room types, i.e. no offices or toilets...
		$query->where('t.suppress = 0');

		$this->setSearchFilter($query, ['r.name']);
		$this->setValueFilters($query, ['buildingID', 'roomtypeID']);
		$this->setCampusFilter($query, 'b');

		if ($roomIDs = Helpers\Input::getFilterIDs('room'))
		{
			$query->where('r.id IN (' . implode(',', $roomIDs) . ')');
		}

		if ($capacity = Helpers\Input::getInt('capacity'))
		{
			$query->where("r.capacity >= $capacity");
		}

		$query->order($this->defaultOrdering);

		return $query;
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
		parent::populateState($ordering = null, $direction = null);

		$list        = Helpers\Input::getListItems();
		$date        = strtotime($list->get('date')) ? $list->get('date') : date('Y-m-d');
		$defaultGrid = Helpers\Grids::getDefault();
		if ($campusID = Helpers\Input::getParams()->get('campusID'))
		{
			$defaultGrid = Helpers\Campuses::getGridID($campusID);
		}

		$this->setState('list.template', (int) $list->get('template', self::DAY));
		$this->setState('list.gridID', (int) $list->get('gridID', $defaultGrid));
		$this->setState('list.date', $date);
	}
}
