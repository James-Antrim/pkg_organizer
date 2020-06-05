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
 * Class retrieves information for a filtered set of persons.
 */
class Persons extends ListModel
{
	protected $defaultOrdering = 'p.surname, p.forename';

	protected $filter_fields = ['organizationID', 'suppress'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$query = $this->_db->getQuery(true);
		$query->select('DISTINCT p.id, surname, forename, username, code, o.id AS organizationID')
			->from('#__organizer_persons AS p')
			->leftJoin('#__organizer_associations AS a ON a.personID = p.id')
			->leftJoin('#__organizer_organizations AS o ON o.id = a.id');

		$this->setSearchFilter($query, ['surname', 'forename', 'username', 'code']);
		$this->setIDFilter($query, 'organizationID', 'filter.organizationID');
		$this->setValueFilters($query, ['p.active', 'p.suppress']);
		$this->setOrdering($query);

		return $query;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return void populates state properties
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState($ordering, $direction);

		$app     = Helpers\OrganizerHelper::getApplication();
		$filters = $app->getUserStateFromRequest($this->context . '.filter', 'filter', [], 'array');

		if (!array_key_exists('active', $filters))
		{
			$this->setState('filter.active', 1);
		}
	}

}
