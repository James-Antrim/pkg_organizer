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
 * Class retrieves information for a filtered set of campuses.
 */
class Campuses extends ListModel
{
	protected $filter_fields = ['city', 'gridID'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$tag   = Helpers\Languages::getTag();
		$query = $this->_db->getQuery(true);

		$query->select("c1.id, c1.name_$tag as name, c1.address, c1.city, c1.zipCode, c1.location")
			->select("c2.id as parentID, c2.name_$tag as parentName, c2.address as parentAddress")
			->select('c2.city AS parentCity, c2.zipCode AS parentZIPCode')
			->select("g1.id as gridID, g1.name_$tag as gridName")
			->select("g2.id as parentGridID, g2.name_$tag as parentGridName")
			->from('#__organizer_campuses AS c1')
			->leftJoin('#__organizer_grids AS g1 ON g1.id = c1.gridID')
			->leftJoin('#__organizer_campuses AS c2 ON c2.id = c1.parentID')
			->leftJoin('#__organizer_grids AS g2 ON g2.id = c2.gridID');

		$searchColumns = [
			'c1.name_de',
			'c1.name_en',
			'c1.city',
			'c1.address',
			'c1.zipCode',
			'c2.city',
			'c2.address',
			'c2.zipCode'
		];
		$this->setSearchFilter($query, $searchColumns);
		$this->setCityFilter($query);
		$this->setGridFilter($query);

		return $query;
	}

	/**
	 * Filters according to the selected city.
	 *
	 * @param   object  $query  the query object
	 *
	 * @return void
	 */
	private function setCityFilter($query)
	{
		$value = $this->state->get('filter.city', '');

		if ($value === '')
		{
			return;
		}

		/**
		 * Special value reserved for empty filtering. Since an empty is dependent upon the column default, we must
		 * check against multiple 'empty' values. Here we check against empty string and null. Should this need to
		 * be extended we could maybe add a parameter for it later.
		 */
		if ($value == '-1')
		{
			$query->where("city = ''");

			return;
		}

		$query->where("(c1.city = '$value' OR (c1.city = '' AND c2.city = '$value'))");
	}

	/**
	 * Filters according to the selected grid.
	 *
	 * @param   object  $query  the query object
	 *
	 * @return void
	 */
	private function setGridFilter($query)
	{
		$value = $this->state->get('filter.gridID', '');

		if ($value === '')
		{
			return;
		}

		/**
		 * Special value reserved for empty filtering. Since an empty is dependent upon the column default, we must
		 * check against multiple 'empty' values. Here we check against empty string and null. Should this need to
		 * be extended we could maybe add a parameter for it later.
		 */
		if ($value == '-1')
		{
			$query->where('g1.id IS NULL and g2.id IS NULL');

			return;
		}

		$query->where("(g1.id = '$value' OR (g1.id IS NULL AND g2.id = '$value'))");
	}
}
