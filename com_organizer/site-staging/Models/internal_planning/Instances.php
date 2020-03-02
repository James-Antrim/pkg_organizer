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
use Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of instances.
 */
class Instances extends ListModel
{
	protected $defaultOrdering = 'name';

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 * @throws Exception
	 */
	protected function getListQuery()
	{
		$tag = Helpers\Languages::getTag();

		$conditions                     = Helpers\Instances::getConditions();
		$conditions['isEventsRequired'] = true;

		$query = Helpers\Instances::getInstanceQuery($conditions);

		$query->innerJoin('#__organizer_terms AS t ON t.id = u.termID')
			->select("DISTINCT i.id")
			->select("e.name_$tag AS name")
			->select("t.name_$tag AS term")
			->select("u.id AS unitID")
			->select("b.date AS date");

		$this->setSearchFilter($query, ['e.name_de', 'e.name_en']);
		$this->setValueFilters($query, ['u.termID']);
		$this->setDateStatusFilter($query, 'status', 'b.date', 'b.date');
		$this->setTimeBlockFilter($query);
		$this->setOrdering($query);

		return $query;
	}

	/**
	 * Adds the filter settings for time blocks of an instance
	 *
	 * @param   object &$query  the query object
	 */
	private function setTimeBlockFilter(&$query)
	{

		$value   = $this->state->get("filter.timeBlock");
		$timings = explode(",", $value);

		if (sizeof($timings) == 2)
		{
			$query->where("startTime = '{$timings[0]}' and endTime = '{$timings[1]}'");
		}
	}
}