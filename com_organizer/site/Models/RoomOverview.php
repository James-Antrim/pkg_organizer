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
 * Retrieves lesson and event data for a filtered set of rooms.
 */
class RoomOverview extends ListModel
{
	private const DAY = 1;

	protected $defaultLimit = 25;

	protected $defaultOrdering = 'r.name';

	protected $filter_fields = ['campusID', 'buildingID', 'effCapacity', 'roomtypeID'];

	/**
	 * @inheritDoc
	 */
	protected function filterFilterForm(Form &$form)
	{
		parent::filterFilterForm($form);

		if (Helpers\Input::getParams()->get('campusID', 0))
		{
			$form->removeField('campusID', 'filter');
			unset($this->filter_fields[array_search('campusID', $this->filter_fields)]);
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function getListQuery()
	{
		$tag   = Helpers\Languages::getTag();
		$query = $this->_db->getQuery(true);

		$query->select('r.id, r.name AS name, r.effCapacity')
			->select("t.id AS roomtypeID, t.name_$tag AS typeName, t.description_$tag AS typeDesc")
			->from('#__organizer_rooms AS r')
			->leftJoin('#__organizer_roomtypes AS t ON t.id = r.roomtypeID')
			->leftJoin('#__organizer_buildings AS b ON b.id = r.buildingID')
            ->leftJoin('#__organizer_room_equipment AS re ON r.id = re.roomId')
            ->leftJoin('#__organizer_roomtype_equipment AS rt_eq ON rt_eq.roomtypeID = t.id')
			->where('r.active = 1')
			->where('t.suppress = 0');

		// Only display public room types, i.e. no offices or toilets...
		$query->where('t.suppress = 0');

		$this->setSearchFilter($query, ['r.name']);
		$this->setValueFilters($query, ['buildingID', 'r.roomtypeID']);
		$this->setCampusFilter($query, 'b');

		if ($roomIDs = Helpers\Input::getFilterIDs('room'))
		{
			$query->where('r.id IN (' . implode(',', $roomIDs) . ')');
		}

		if ($capacity = $this->state->get('filter.effCapacity'))
		{
			$query->where("r.effCapacity >= $capacity");
		}

        $filter_equipment = $this->state->get('filter.equipmentID');
        if($filter_equipment > 0){
            $query->where('rt_eq.equipmentID ='.$this->_db->q($filter_equipment).' OR '.'re.equipmentID ='.$this->_db->q($filter_equipment));
        }


        $query->order($this->defaultOrdering);
        //echo "<pre>";echo $query;echo "</pre>";
		return $query;
	}

	/**
	 * @inheritDoc
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
			$this->setState('filter.campusID', $campusID);
		}

		$this->setState('list.template', (int) $list->get('template', self::DAY));
		$this->setState('list.gridID', (int) $list->get('gridID', $defaultGrid));
		$this->setState('list.date', $date);
	}
}
