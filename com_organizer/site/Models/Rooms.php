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
use Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of rooms.
 */
class Rooms extends ListModel
{
	use Activated;

	protected $defaultOrdering = 'r.name';

	protected $filter_fields = ['campusID', 'buildingID', 'roomtypeID', 'virtual','room_archetypeID','equipmentID'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$tag   = Helpers\Languages::getTag();
		$query = $this->_db->getQuery(true);

		$query->select('r.id, r.code, r.name AS roomName, r.active, r.effCapacity')
			->select("t.id AS roomtypeID, t.name_$tag AS roomType, din.name_$tag as din_name,din.din_code")
			->select('b.id AS buildingID, b.address, b.name AS buildingName, b.location, b.propertyType')
			->select("c1.name_$tag AS campus, c2.name_$tag AS parent, c1.address AS campus_address")
            ->select('din.room_archetypeID')
            ->select('r_eq.equipmentID')
			->from('#__organizer_rooms AS r')
			->leftJoin('#__organizer_roomtypes AS t ON t.id = r.roomtypeID')
            ->leftJoin('#__organizer_room_dintypes AS din ON din.id = t.room_dintypeID')
            ->leftJoin('#__organizer_room_equipment AS r_eq ON r.id = r_eq.roomID')
            ->leftJoin('#__organizer_roomtype_equipment AS rt_eq ON rt_eq.roomtypeID = t.id')
			->leftJoin('#__organizer_buildings AS b ON b.id = r.buildingID')
			->leftJoin('#__organizer_campuses AS c1 ON c1.id = b.campusID')
			->leftJoin('#__organizer_campuses AS c2 ON c2.id = c1.parentID');

		$this->setActiveFilter($query, 'r');
		$this->setSearchFilter($query, ['r.name', 'b.name', 't.name_de', 't.name_en']);
		$this->setValueFilters($query, ['buildingID', 'r.roomtypeID', 'virtual','room_archetypeID']);
		$this->setCampusFilter($query, 'b');
        $state    = $this->getState();

        $filter_equipment = $state->get('filter.equipmentID');
        if($filter_equipment > 0){
            $query->where('rt_eq.equipmentID ='.$this->_db->q($filter_equipment).' OR '.'r_eq.equipmentID ='.$this->_db->q($filter_equipment));
        }

		$this->setOrdering($query);
        $query->group('r.id');
		return $query;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return void populates state properties
	 * @noinspection PhpDocSignatureInspection
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState($ordering, $direction);

		if ($format = Helpers\Input::getCMD('format') and in_array($format, ['pdf', 'xls']))
		{
			$this->setState('list.limit', 0);
		}
	}


}
