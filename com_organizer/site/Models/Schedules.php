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
use Organizer\Helpers\Can;
use Organizer\Helpers\Languages;

/**
 * Class retrieves information for a filtered set of schedules.
 */
class Schedules extends ListModel
{
	protected $defaultOrdering = 'created';

	protected $defaultDirection = 'DESC';

	protected $filter_fields = ['active', 'departmentID', 'termID'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$dbo   = $this->getDbo();
		$tag   = Languages::getTag();
		$query = $dbo->getQuery(true);

		$createdParts = ['s.creationDate', 's.creationTime'];
		$query->select('s.id, s.active, s.creationDate, s.creationTime')
			->select($query->concatenate($createdParts, ' ') . ' AS created ')
			->select("d.id AS departmentID, d.shortName_$tag AS departmentName")
			->select("term.id AS termID, term.name_$tag AS termName")
			->select('u.name AS userName')
			->from('#__organizer_schedules AS s')
			->innerJoin('#__organizer_departments AS d ON d.id = s.departmentID')
			->innerJoin('#__organizer_terms AS term ON term.id = s.termID')
			->leftJoin('#__users AS u ON u.id = s.userID');

		$authorizedDepartments = implode(', ', Can::scheduleTheseOrganizations());
		$query->where("d.id IN ($authorizedDepartments)");

		$this->setValueFilters($query, ['departmentID', 'termID', 'active']);

		$this->setOrdering($query);

		return $query;
	}
}
