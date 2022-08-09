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
	public const UPCOMING_INSTANCES = 0, CURRENT_INSTANCES = 1, MIXED_PLAN = 2, CONTENT_DISPLAY = 3;

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
		$templateKey = $this->state->get('filter.display', '');

		if ($templateKey === '')
		{
			return;
		}

		$templateKey = (int) $templateKey;
		$templates   = [self::UPCOMING_INSTANCES, self::CURRENT_INSTANCES, self::MIXED_PLAN, self::CONTENT_DISPLAY];

		if (!in_array($templateKey, $templates))
		{
			return;
		}

		$where = "m.display = $templateKey";

		$params              = Helpers\Input::getParams();
		$defaultDisplay      = $params->get('display', '');
		$useComponentDisplay = (!empty($defaultDisplay) and $templateKey == $defaultDisplay);

		if ($useComponentDisplay)
		{
			$query->where("( $where OR useDefaults = 1)");

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
		$params  = Helpers\Input::getParams();
		$content = (string) $this->state->get('filter.content', '');

		if ($content === '')
		{
			return;
		}

		$content        = $content === '-1' ? '' : $content;
		$defaultContent = $params->get('content', '');
		$where          = 'm.content = ' . Database::quote($content);

		if ($content === $defaultContent)
		{
			$query->where("($where OR useDefaults = 1)");

			return;
		}

		$query->where($where);
	}
}
