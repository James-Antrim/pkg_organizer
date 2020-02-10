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
 * Class retrieves information for a filtered set of (degree) programs.
 */
class Programs extends ListModel
{
	protected $filter_fields = ['degreeID', 'organizationID', 'fieldID', 'frequencyID', 'accredited'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$authorizedDepts = Can::documentTheseOrganizations();
		$tag             = Languages::getTag();

		$query     = $this->_db->getQuery(true);
		$linkParts = ["'index.php?option=com_organizer&view=program_edit&id='", 'dp.id'];
		$query->select("DISTINCT dp.id AS id, dp.name_$tag AS programName, accredited")
			->select($query->concatenate($linkParts, '') . ' AS link')
			->from('#__organizer_programs AS dp')
			->select('d.abbreviation AS degree')
			->leftJoin('#__organizer_degrees AS d ON d.id = dp.degreeID')
			->leftJoin('#__organizer_fields AS f ON f.id = dp.fieldID')
			->select("o.shortName_$tag AS organization")
			->leftJoin('#__organizer_organizations AS o ON o.id = dp.organizationID')
			->where('(dp.organizationID IN (' . implode(',', $authorizedDepts) . ') OR dp.organizationID IS NULL)');

		$searchColumns = ['dp.name_de', 'dp.name_en', 'accredited', 'd.name', 'description_de', 'description_en'];
		$this->setSearchFilter($query, $searchColumns);
		$this->setValueFilters($query, ['degreeID', 'organizationID', 'fieldID', 'frequencyID', 'accredited']);

		$this->setOrdering($query);

		return $query;
	}
}
