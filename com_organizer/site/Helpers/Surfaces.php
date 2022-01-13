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
 * Class provides general functions for retrieving DIN surface data.
 */
class Surfaces extends ResourceHelper implements Selectable
{
	use Filtered;

	/**
	 * @inheritDoc
	 */
	public static function getOptions(): array
	{
		$options = [];
		foreach (self::getResources() as $surface)
		{
			$options[] = HTML::_('select.option', $surface['id'], $surface['name']);
		}

		return $options;
	}

	/**
	 * @inheritDoc
	 */
	public static function getResources($associated = true): array
	{
		$query = Database::getQuery();
		$tag   = Languages::getTag();
		$query->select("DISTINCT s.id")
			->select($query->concatenate(['s.code', "' - '", "s.name_$tag"], '') . ' AS name')
			->from('#__organizer_surfaces AS s')
			->order('name');

		if ($associated)
		{
			$query->innerJoin('#__organizer_roomtypes AS t ON t.surfaceID = s.id')
				->innerJoin('#__organizer_rooms AS r ON r.roomtypeID = t.id');
		}
		else
		{
			$query->leftJoin('#__organizer_roomtypes AS t ON t.surfaceID = s.id')
				->leftJoin('#__organizer_rooms AS r ON r.roomtypeID = t.id');
		}

		self::addResourceFilter($query, 'building', 'b1', 'r');

		// This join is used specifically to filter campuses independent of buildings.
		$query->leftJoin('#__organizer_buildings AS b2 ON b2.id = r.buildingID');
		self::addCampusFilter($query, 'b2');

		Database::setQuery($query);

		return Database::loadAssocList('id');
	}

}
