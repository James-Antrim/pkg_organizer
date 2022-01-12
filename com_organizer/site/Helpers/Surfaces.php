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
use Organizer\Tables\Grids as Table;

/**
 * Class provides general functions for retrieving DIN surface data.
 */
class Surfaces extends ResourceHelper implements Selectable
{
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
	public static function getResources(): array
	{
		$query = Database::getQuery();
		$tag   = Languages::getTag();
		$query->select("DISTINCT s.id")
			->select($query->concatenate(['s.code', "' - '", "s.name_$tag"], '') . ' AS name')
			->from('#__organizer_surfaces AS s')
			->innerJoin('#__organizer_roomtypes AS t ON t.surfaceID = s.id');
		Database::setQuery($query);

		return Database::loadAssocList('id');
	}

}
