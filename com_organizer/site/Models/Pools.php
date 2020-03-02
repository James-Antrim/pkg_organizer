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
 * Class retrieves information for a filtered set of (subject) pools.
 */
class Pools extends ListModel
{
	protected $filter_fields = ['organizationID', 'fieldID', 'programID'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$tag   = Helpers\Languages::getTag();
		$query = $this->_db->getQuery(true);

		$query->select("DISTINCT p.id, p.fullName_$tag AS name, p.fieldID")
			->from('#__organizer_pools AS p')
			->leftJoin('#__organizer_associations AS a ON a.poolID = p.id');

		$authorized = Helpers\Can::documentTheseOrganizations();
		$query->where('(a.organizationID IN (' . implode(',', $authorized) . ') OR a.organizationID IS NULL)');

		$searchColumns = [
			'p.fullName_de',
			'p.shortName_de',
			'p.abbreviation_de',
			'p.fullName_en',
			'p.shortName_en',
			'p.abbreviation_en'
		];
		$this->setSearchFilter($query, $searchColumns);
		$this->setValueFilters($query, ['organizationID', 'fieldID']);

		$programID = $this->state->get('filter.programID', '');
		Helpers\Pools::setProgramFilter($query, $programID, 'pool');

		$this->setOrdering($query);

		return $query;
	}
}
