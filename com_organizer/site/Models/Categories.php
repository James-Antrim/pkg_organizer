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
 * Class retrieves information for a filtered set of categories.
 */
class Categories extends ListModel
{
	use Activated;

	protected $defaultOrdering = 'name';

	protected $filter_fields = ['organizationID'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery(): JDatabaseQuery
	{
		$tag = Helpers\Languages::getTag();

		/* @var QueryMySQLi $query */
		$query = Database::getQuery();
		$query->select("DISTINCT cat.id, cat.code, cat.name_$tag AS name, cat.active")
			->from('#__organizer_categories AS cat')
			->innerJoin('#__organizer_associations AS a ON a.categoryID = cat.id');

		$authorized = implode(",", Helpers\Can::scheduleTheseOrganizations());
		$query->where("a.organizationID IN ($authorized)");

		$this->setActiveFilter($query, 'cat');
		$this->setSearchFilter($query, ['cat.name_de', 'cat.name_en', 'cat.code']);
		$this->setValueFilters($query, ['organizationID', 'programID']);
		$this->setOrdering($query);

		return $query;
	}
}
