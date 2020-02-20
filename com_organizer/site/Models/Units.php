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
	use Filtered;
	protected $defaultOrdering = 'name';

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$tag = Helpers\Languages::getTag();

		$query    = $this->_db->getQuery(true);
		$subQuery = $this->_db->getQuery(true);

		$subQuery->select('MIN(date) AS start, MAX(date) AS end, i.unitID')
			->from('#__organizer_blocks AS b')
			->innerJoin('#__organizer_instances AS i ON i.blockID = b.id')
			->where("i.delta!='removed'")
			->group('i.unitID');

		$query->select('u.id')
			->select("ev.id as eventID, ev.name_$tag as name")
			->select("g.id AS gridID, g.name_$tag AS grid")
			->select("r.id AS runID, r.name_$tag AS run")
			->select("sq.start, sq.end");

		$query->from('#__organizer_units AS u')
			->innerJoin('#__organizer_instances AS i ON i.unitID = u.id')
			->innerJoin('#__organizer_events AS ev ON ev.id = i.eventID')
			->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
			->innerJoin('#__organizer_grids AS g ON g.id = u.gridID')
			->leftJoin('#__organizer_runs AS r ON r.id = u.runID')
			->innerJoin("($subQuery) AS sq ON sq.unitID = u.id")
			->group('u.id');

		$this->setSearchFilter($query, ['ev.name_de', 'ev.name_en']);
		$this->setValueFilters($query, ['u.organizationID', 'u.termID', 'u.gridID', 'u.runID']);
		$this->setDateStatusFilter($query, 'status', 'sq.start', 'sq.end');

		return $query;
	}
}
