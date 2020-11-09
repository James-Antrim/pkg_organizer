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

	protected $filter_fields = ['campusID', 'buildingID', 'roomtypeID'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$tag   = Helpers\Languages::getTag();
		$query = $this->_db->getQuery(true);

		$query->select('r.id, r.code, r.name AS roomName, r.active')
			->select("t.id AS roomtypeID, t.name_$tag AS roomType")
			->select('b.id AS buildingID, b.name AS buildingName')
			->from('#__organizer_rooms AS r')
			->leftJoin('#__organizer_roomtypes AS t ON t.id = r.roomtypeID')
			->leftJoin('#__organizer_buildings AS b ON b.id = r.buildingID')
			->leftJoin('#__organizer_campuses AS c ON (c.id = b.campusID OR c.parentID = b.campusID)');

		$this->setActiveFilter($query, 'r');
		$this->setSearchFilter($query, ['r.name', 'b.name', 't.name_de', 't.name_en']);
		$this->setValueFilters($query, ['buildingID', 'roomtypeID']);
		$this->setCampusFilter($query, 'b');

		$this->setOrdering($query);

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
