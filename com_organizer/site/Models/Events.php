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
 * Class retrieves information for a filtered set of events.
 */
class Events extends ListModel
{
	protected $defaultOrdering = 'code';

	protected $filter_fields = ['campusID', 'categoryID', 'groupID', 'organizationID', 'preparatory'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$tag   = Helpers\Languages::getTag();
		$query = $this->_db->getQuery(true);
		$query->select("DISTINCT e.id AS id, e.name_$tag as name, e.organizationID, e.code")
			->select("o.id AS organizationID, o.shortName_$tag AS organization")
			->select("c.id AS campusID, c.name_$tag AS campus")
			->from('#__organizer_events AS e')
			->innerJoin('#__organizer_organizations AS o ON o.id = e.organizationID')
			->leftJoin('#__organizer_campuses AS c ON c.id = e.campusID');

		if ($this->adminContext)
		{
			$authorized = implode(', ', Helpers\Can::scheduleTheseOrganizations());
			$query->where("o.id IN ($authorized)");
		}

		$this->setSearchFilter($query, ['e.name_de', 'e.name_en', 'e.subjectNo']);
		$this->setValueFilters($query, ['e.organizationID', 'e.campusID', 'e.preparatory']);

		$this->setOrdering($query);

		return $query;
	}
}