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
 * Class retrieves information for a filtered set of runs.
 */
class Runs extends ListModel
{
	protected $defaultOrdering = 'name';

	protected $filter_fields = ['termID'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$tag   = Helpers\Languages::getTag();
		$query = $this->_db->getQuery(true);
		$query->select("r.id, r.name_$tag as name, r.run, r.termID, t.name_$tag as term")
			->from('#__organizer_runs AS r')
			->leftJoin('#__organizer_terms AS t ON t.id = r.termID');

		$this->setSearchFilter($query, ['name_de', 'name_en']);
		$this->setValueFilters($query, ['termID']);

		$this->setOrdering($query);

		return $query;
	}
}