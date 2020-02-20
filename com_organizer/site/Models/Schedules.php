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
 * Class retrieves information for a filtered set of schedules.
 */
class Schedules extends ListModel
{
	protected $defaultOrdering = 'created';

	protected $defaultDirection = 'DESC';

	protected $filter_fields = ['active', 'organizationID', 'termID'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$dbo   = $this->getDbo();
		$tag   = Helpers\Languages::getTag();
		$query = $dbo->getQuery(true);

		$createdParts = ['s.creationDate', 's.creationTime'];
		$query->select('s.id, s.active, s.creationDate, s.creationTime')
			->select($query->concatenate($createdParts, ' ') . ' AS created ')
			->select("o.id AS organizationID, o.shortName_$tag AS organizationName")
			->select("term.id AS termID, term.name_$tag AS termName")
			->select('u.name AS userName')
			->from('#__organizer_schedules AS s')
			->innerJoin('#__organizer_organizations AS o ON o.id = s.organizationID')
			->innerJoin('#__organizer_terms AS term ON term.id = s.termID')
			->leftJoin('#__users AS u ON u.id = s.userID');

		$authorized = implode(', ', Helpers\Can::scheduleTheseOrganizations());
		$query->where("o.id IN ($authorized)");

		$this->setValueFilters($query, ['organizationID', 'termID', 'active']);

		$this->setOrdering($query);

		return $query;
	}
}
