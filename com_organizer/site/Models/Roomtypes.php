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
class Roomtypes extends ListModel
{
	protected $filter_fields = ['surfaceID' => 'surfaceID'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$tag = Helpers\Languages::getTag();

		$query = $this->_db->getQuery(true);
		$query->select("DISTINCT t.id, t.name_$tag AS name, t.capacity, t.code")
			->select($query->concatenate(['s.code', "' - '", "s.name_$tag"], '') . ' AS surface')
			->from('#__organizer_roomtypes AS t')
			->innerJoin('#__organizer_surfaces AS s ON s.id = t.surfaceID');

		$this->setIDFilter($query, 's.id', 'filter.surfaceID');
		$this->setSearchFilter($query, ['t.code', 't.name_de', 't.name_en', 't.capacity']);
		$this->setOrdering($query);

		return $query;
	}
}
