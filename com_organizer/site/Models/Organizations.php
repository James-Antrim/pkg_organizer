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
 * Class retrieves information for a filtered set of organizations.
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
		$authorized = Helpers\Can::manageTheseOrganizations();
		$tag        = Helpers\Languages::getTag();

		// Create the query
		$query  = $this->_db->getQuery(true);
		$select = "o.id, o.shortName_$tag AS shortName, o.fullName_$tag AS name, a.rules, ";
		$parts  = ["'index.php?option=com_organizer&view=organization_edit&id='", 'o.id'];
		$select .= $query->concatenate($parts, '') . ' AS link ';
		$query->select($select)
			->from('#__organizer_organizations AS o')
			->leftJoin('#__assets AS a ON a.id = o.asset_id')
			->where('o.id IN (' . implode(',', $authorized) . ')');

		$searchColumns = [
			'abbreviation_de',
			'abbreviation_en',
			'fullName_de',
			'fullName_en',
			'name_de',
			'name_en',
			'shortName_de',
			'shortName_en'
		];

		$this->setSearchFilter($query, $searchColumns);

		$this->setOrdering($query);

		return $query;
	}
}
