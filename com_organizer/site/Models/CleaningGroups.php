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
use Organizer\Adapters\Database;
use Organizer\Adapters\Queries\QueryMySQLi;
use Organizer\Helpers;

/**
 * Class retrieves the data regarding a filtered set of buildings.
 */
class CleaningGroups extends ListModel
{
	protected $filter_fields = ['relevant'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery(): JDatabaseQuery
	{
		/* @var QueryMySQLi $query */
		$query = Database::getQuery();
		$tag   = Helpers\Languages::getTag();

		$query->select("*, name_$tag AS name")->from('#__organizer_cleaning_groups');

		$this->setSearchFilter($query, ['name_de', 'name_en']);

		$relevant = $this->state->get('filter.relevant');

		if (is_numeric($relevant) and in_array((int) $relevant, [0, 1]))
		{
			$query->where("relevant = $relevant");
		}

		$this->setOrdering($query);

		return $query;
	}
}
