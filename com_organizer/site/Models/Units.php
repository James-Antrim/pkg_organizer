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
 * Class retrieves the data regarding a filtered set of units.
 */
class Units extends ListModel
{
	protected $filter_fields = [
		//'categoryID',
		'gridID',
		//'groupID',
		//'methodID',
		'organizationID',
		'status',
		'termID',
	];

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 */
	public function getItems()
	{
		$items = parent::getItems();

		foreach ($items as $item)
		{
			$item->name = Helpers\Units::getEventNames($item->id, '<br>');
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
		$modified = date('Y-m-d h:i:s', strtotime('-2 Weeks'));
		$termID   = $this->state->get('filter.termID');
		$query    = $this->_db->getQuery(true);
		$tag      = Helpers\Languages::getTag();

		$query->select('u.id, u.code, u.courseID, u.delta AS status, u.endDate, u.modified, u.startDate')
			->select("g.name_$tag AS grid")
			//->select("r.name_$tag AS run")
			->select("m.name_de AS method")
			->from('#__organizer_units AS u')
			->innerJoin('#__organizer_grids AS g ON g.id = u.gridID')
			//->leftJoin('#__organizer_runs AS r ON r.id = u.runID')
			->innerJoin('#__organizer_instances AS i ON i.unitID = u.id')
			->innerJoin('#__organizer_instance_persons AS ip ON ip.instanceID = i.id')
			->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ip.id')
			->innerJoin('#__organizer_associations AS a ON a.groupID = ig.groupID')
			->leftJoin('#__organizer_methods AS m ON m.id = i.methodID')
			->where("(u.delta != 'removed' OR u.modified > '$modified')")
			->where("u.termid = $termID")
			->order('u.startDate, u.endDate')
			->group('u.id');

		if ($organizationID = $this->state->get('filter.organizationID'))
		{
			$query->where("a.organizationID = $organizationID");
		}
		else
		{
			$organizationIDs = implode(',', Helpers\Can::scheduleTheseOrganizations());
			$query->where("a.organizationID IN ($organizationIDs)");
		}

		if ($search = $this->state->get('filter.search'))
		{
			$query->innerJoin('#__organizer_events AS e ON e.id = i.eventID');
			$this->setSearchFilter($query, ['e.name_de', 'e.name_en', 'u.code']);
		}

		$this->setValueFilters($query, ['u.gridID', 'u.runID']);
		$this->setStatusFilter($query, 'u');

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
		parent::populateState($ordering, $direction);

		if (!$this->state->get('filter.termID'))
		{
			$this->setState('filter.termID', Helpers\Terms::getCurrentID());
		}
	}
}
