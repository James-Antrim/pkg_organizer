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
 * Class retrieves information for a filtered set of colors.
 */
class Colors extends ListModel
{
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

		$query->select("id, name_$tag AS name, color")
			->from('#__organizer_colors');

		$this->setSearchFilter($query, ['name_de', 'name_en', 'color']);
		$this->setValueFilters($query, ['color']);
		$this->setIDFilter($query, 'id', 'filter.name');

		$this->setOrdering($query);

		return $query;
	}
}
