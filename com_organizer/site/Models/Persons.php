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

/**
 * Class retrieves information for a filtered set of persons.
 */
class Persons extends ListModel
{
	protected $defaultOrdering = 'p.surname, p.forename';

	protected $filter_fields = ['departmentID'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$query  = $this->_db->getQuery(true);
		$select = 'DISTINCT p.id, surname, forename, username, untisID, d.id AS departmentID, ';
		$parts  = ["'index.php?option=com_organizer&view=person_edit&id='", 'p.id'];
		$select .= $query->concatenate($parts, '') . ' AS link ';
		$query->select($select);
		$query->from('#__organizer_persons AS p')
			->leftJoin('#__organizer_associations AS a ON a.personID = p.id')
			->leftJoin('#__organizer_departments AS d ON d.id = a.id');

		$this->setSearchFilter($query, ['surname', 'forename', 'username', 'untisID']);
		$this->setIDFilter($query, 'departmentID', 'list.departmentID');
		$this->setValueFilters($query, ['forename', 'username', 'untisID']);

		$this->setOrdering($query);

		return $query;
	}
}
