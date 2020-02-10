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
 * Class retrieves information for a filtered set of departments.
 */
class Organizations extends ListModel
{
	protected $defaultOrdering = 'shortName';

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$allowedDepartments = Can::manageTheseOrganizations();
		$tag                = Languages::getTag();

		// Create the query
		$query  = $this->_db->getQuery(true);
		$select = "o.id, o.shortName_$tag AS shortName, o.name_$tag AS name, a.rules, ";
		$parts  = ["'index.php?option=com_organizer&view=department_edit&id='", 'o.id'];
		$select .= $query->concatenate($parts, '') . ' AS link ';
		$query->select($select);
		$query->from('#__organizer_organizations AS o');
		$query->innerJoin('#__assets AS a ON a.id = o.asset_id');
		$query->where('o.id IN (' . implode(',', $allowedDepartments) . ')');

		$this->setSearchFilter($query, ['shortName_de', 'name_de', 'shortName_en', 'name_en']);

		$this->setOrdering($query);

		return $query;
	}
}
