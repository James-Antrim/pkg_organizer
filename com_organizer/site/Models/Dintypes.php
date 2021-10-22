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
 * Class retrieves information for a filtered set of colors.
 */
class Dintypes extends ListModel
{
	protected $filter_fields = ['archetypeID'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery(): JDatabaseQuery
	{
		$tag   = Helpers\Languages::getTag();
		$query = $this->_db->getQuery(true);

		$query->select("id, LPAD(id, 3, '0') AS code, name_$tag AS name")->from('#__organizer_dintypes');

		$term = $this->state->get('filter.search', '');
		if ($term !== '')
		{
			if (is_numeric($term))
			{
				$term = (int) $term;
				$query->where("id = $term");

			}
			else
			{
				$this->setSearchFilter($query, ['name_de', 'name_en']);
			}
		}

		$this->setValueFilters($query, ['archetypeID']);

		return $query;
	}
}
