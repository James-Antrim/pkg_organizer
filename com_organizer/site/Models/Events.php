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
use Organizer\Helpers\Languages;

/**
 * Class retrieves information for a filtered set of events.
 */
class Events extends ListModel
{
	protected $defaultOrdering = 'name,department';

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$tag   = Languages::getTag();
		$query = $this->_db->getQuery(true);
		$query->select("DISTINCT ev.id AS id, ev.name_$tag as name, ev.organizationID, ev.campusID")
			->select("ev.maxParticipants, ev.registrationType, ev.subjectNo, ev.preparatory")
			->select("o.id AS organizationID, o.shortName_$tag AS department")
			->select("cp.id AS campusID, cp.name_$tag AS campus")
			->from('#__organizer_events AS ev')
			->leftJoin('#__organizer_organizations AS o ON o.id = ev.organizationID')
			->leftJoin('#__organizer_campuses AS cp ON cp.id = ev.campusID');

		$this->setSearchFilter($query, ['ev.name_de', 'ev.name_en', 'ev.subjectNo']);
		$this->setValueFilters($query, ['ev.organizationID', 'ev.campusID', 'ev.preparatory']);

		$this->setOrdering($query);

		return $query;
	}
}