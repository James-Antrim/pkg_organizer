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
 * Class retrieves information for a filtered set of room types.
 */
class Equipment extends ListModel
{
	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery(): JDatabaseQuery
	{
		$tag = Helpers\Languages::getTag();

		$query = $this->_db->getQuery(true);
		$query->select("DISTINCT e.*, e.name_$tag AS name")
			->from('#__organizer_equipment AS e');

		$this->setSearchFilter($query, ['e.code', 'e.name_de', 'e.name_en']);
		$this->setOrdering($query);

		return $query;
	}
}
