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
use Organizer\Tables\Roles as Table;

/**
 * Class provides generalized functions regarding dates and times.
 */
class Roles extends ResourceHelper implements Selectable
{
	/**
	 * Returns the color value for a given colorID.
	 *
	 * @param   int  $roleID  the id of the color
	 * @param   int  $count   the number of entries
	 *
	 * @return string the label text for the role
	 */
	public static function getLabel(int $roleID, int $count): string
	{
		$tag    = Languages::getTag();
		$column = $count > 1 ? "plural_$tag" : "name_$tag";
		$table  = new Table();

		return $table->load($roleID) ? $table->$column : '';
	}

	/**
	 * @inheritDoc
	 */
	public static function getOptions(): array
	{
		$options = [];
		foreach (self::getResources() as $role)
		{
			$options[] = HTML::_('select.option', $role['id'], $role['name']);
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
		$query->select("DISTINCT *, name_$tag AS name")
			->from('#__organizer_roles')
			->order('name');
		Database::setQuery($query);

		return Database::loadAssocList('id');
	}
}
