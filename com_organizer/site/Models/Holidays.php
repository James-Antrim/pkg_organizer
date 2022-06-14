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
	private const EXPIRED = 1, NOT_EXPIRED = 0;

	protected $defaultOrdering = 'startDate';

	protected $filter_fields = ['termID', 'type'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery(): JDatabaseQuery
	{
		$tag   = Helpers\Languages::getTag();
		$query = Database::getQuery();
		$query->select("h.*, h.name_$tag as name, t.name_$tag as term")
			->from('#__organizer_holidays AS h')
			->innerJoin('#__organizer_terms AS t ON t.startDate <= h.startDate AND t.endDate >= h.endDate');

		$this->setIDFilter($query, 't.id', 'filter.termID');
		$this->setSearchFilter($query, ['h.name_de', 'h.name_en']);
		$this->setValueFilters($query, ['type']);

		switch ((int) $this->state->get('filter.status'))
		{
			case self::EXPIRED:
				$query->where('h.endDate < CURDATE()');
				break;
			case self::NOT_EXPIRED:
				$query->where('h.endDate >= CURDATE()');
				break;
		}

		$this->setOrdering($query);

		return $query;
	}

	/**
	 * @inheritDoc
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState($ordering, $direction);

		$status = (int) $this->state->get('filter.status');
		$this->setState('filter.status', $status);
	}
}