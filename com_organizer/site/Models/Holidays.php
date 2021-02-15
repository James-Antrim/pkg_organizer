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
use Organizer\Adapters\Database;
use Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of holidays.
 */
class Holidays extends ListModel
{
	private const EXPIRED = 1, PENDING = 2;
	protected $defaultOrdering = 'name';

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery(): JDatabaseQuery
	{
		$tag   = Helpers\Languages::getTag();
		$query = Database::getQuery();
		$query->select("id, name_$tag as name, type, startDate, endDate")
			->from('#__organizer_holidays');
		$this->setSearchFilter($query, ['name_de', 'name_en', 'startDate', 'endDate']);
		$this->setValueFilters($query, ['type']);
		$this->filterStatus($query);
		$this->setYearFilter($query);
		$this->setOrdering($query);

		return $query;
	}

	/**
	 * Adds the filter settings for status of holiday
	 *
	 * @param   JDatabaseQuery  $query  the query to modify
	 *
	 * @return void
	 */
	private function filterStatus(JDatabaseQuery $query)
	{
		$listValue   = $this->state->get('list.status');
		$filterValue = $this->state->get('filter.status');

		if (empty($listValue) and empty($filterValue))
		{
			return;
		}

		$value = empty($filterValue) ? $listValue : $filterValue;

		switch ($value)
		{
			case self::EXPIRED :
				$query->where('endDate < CURDATE()');
				break;
			case self::PENDING:
				$query->where('startDate > CURDATE()');
				break;
			default:
				$query->where('endDate BETWEEN CURDATE() AND date_add(CURDATE(), interval 1 YEAR)');
				break;
		}
	}

	/**
	 * Adds the filter settings for displaying year
	 *
	 * @param   JDatabaseQuery  $query  the query to modify
	 *
	 * @return void
	 */
	private function setYearFilter(JDatabaseQuery $query)
	{
		$listValue   = $this->state->get('list.year');
		$filterValue = $this->state->get('filter.year');

		if (empty($listValue) and empty($filterValue))
		{
			return;
		}

		$value = empty($filterValue) ? $listValue : $filterValue;

		$query->where("Year(startDate) = $value");
	}
}