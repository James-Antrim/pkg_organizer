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
 * Class retrieves information for a filtered set of colors.
 */
class Surfaces extends ListModel
{
	protected $filter_fields = ['typeID'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery(): JDatabaseQuery
	{
		$tag   = Helpers\Languages::getTag();
		$query = $this->_db->getQuery(true);
		$query->select("id, code, name_$tag AS name")->from('#__organizer_surfaces')->order('code');
		$this->setSearchFilter($query, ['code', 'name_de', 'name_en']);
		$this->setValueFilters($query, ['typeID']);

		return $query;
	}
}
