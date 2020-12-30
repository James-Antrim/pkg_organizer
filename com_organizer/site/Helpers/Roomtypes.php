<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;

use Organizer\Adapters\Database;

/**
 * Provides general functions for room type access checks, data retrieval and display.
 */
class Roomtypes extends ResourceHelper implements Selectable
{
	use Filtered;

	private const NO = 0, YES = 1;

	/**
	 * @inheritDoc
	 */
	public static function getOptions(): array
	{
		$options = [];
		foreach (self::getResources() as $type)
		{
			$options[] = HTML::_('select.option', $type['id'], $type['name']);
		}

		return $options;
	}

	/**
	 * @inheritDoc
	 * @param   bool  $associated  whether the type needs to be associated with a room
	 * @param   bool  $public
	 */
	public static function getResources($associated = self::YES, $suppress = self::NO): array
	{
		$tag = Languages::getTag();

		$query = Database::getQuery(true);
		$query->select("DISTINCT t.*, t.id AS id, t.name_$tag AS name")
			->from('#__organizer_roomtypes AS t');

		if ($suppress === self::YES or $suppress === self::NO)
		{
			$query->where("t.suppress = $suppress");
		}

		if ($associated === self::YES)
		{
			$query->innerJoin('#__organizer_rooms AS r ON r.roomtypeID = t.id');
		}
		elseif ($associated === self::NO)
		{
			$query->leftJoin('#__organizer_rooms AS r ON r.roomtypeID = t.id');
			$query->where('r.roomtypeID IS NULL');
		}

		self::addResourceFilter($query, 'building', 'b1', 'r');

		// This join is used specifically to filter campuses independent of buildings.
		$query->leftJoin('#__organizer_buildings AS b2 ON b2.id = r.buildingID');
		self::addCampusFilter($query, 'b2');

		$query->order('name');
		Database::setQuery($query);

		return Database::loadAssocList('id');
	}
}
