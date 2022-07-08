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
use Organizer\Adapters\Queries\QueryMySQLi;
use Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of monitors.
 */
class Monitors extends ListModel
{
	protected $defaultOrdering = 'r.name';

	protected $filter_fields = ['content', 'display', 'useDefaults'];

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery(): JDatabaseQuery
	{
		/* @var QueryMySQLi $query */
		$query = Database::getQuery();

		$query->select($this->state->get('list.select', 'm.id, r.name, m.ip, m.useDefaults, m.display, m.content'))
			->from('#__organizer_monitors AS m')
			->leftJoin('#__organizer_rooms AS r ON r.id = m.roomID');

		$this->setSearchFilter($query, ['r.name', 'm.ip']);
		$this->setValueFilters($query, ['useDefaults']);
		$this->addDisplayFilter($query);
		$this->addContentFilter($query);

		$this->setOrdering($query);

		return $query;
	}

	/**
	 * Adds the filter settings for display behaviour
	 *
	 * @param   JDatabaseQuery  $query  the query to modify
	 *
	 * @return void
	 */
	private function addDisplayFilter(JDatabaseQuery $query)
	{
		$requestDisplay = $this->state->get('filter.display', '');

		if ($requestDisplay === '')
		{
			return;
		}

		$where = "m.display ='$requestDisplay'";

		$params              = Helpers\Input::getParams();
		$defaultDisplay      = $params->get('display', '');
		$useComponentDisplay = (!empty($defaultDisplay) and $requestDisplay == $defaultDisplay);
		if ($useComponentDisplay)
		{
			$query->where("( $where OR useDefaults = '1')");

			return;
		}

		$query->where($where);
	}

	/**
	 * Adds the filter settings for displayed content
	 *
	 * @param   JDatabaseQuery  $query  the query to modify
	 *
	 * @return void
	 */
	private function addContentFilter(JDatabaseQuery $query)
	{
		$params         = Helpers\Input::getParams();
		$requestContent = $this->state->get('filter.content', '');

		if ($requestContent === '')
		{
			return;
		}

		$requestContent = $requestContent == '-1' ? '' : $requestContent;
		$where          = "m.content ='$requestContent'";

		$defaultContent      = $params->get('content', '');
		$useComponentContent = ($requestContent == $defaultContent);
		if ($useComponentContent)
		{
			$query->where("( $where OR useDefaults = '1')");

			return;
		}

		$query->where($where);
	}
}
