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

use Organizer\Adapters;
use Organizer\Tables;

/**
 * Class provides general functions for retrieving building data.
 */
class Grids extends ResourceHelper implements Selectable
{
	/**
	 * @inheritDoc
	 */
	public static function getOptions()
	{
		$options = [];
		foreach (self::getResources() as $grid)
		{
			$options[] = HTML::_('select.option', $grid['id'], $grid['name']);
		}

		return $options;
	}

	/**
	 * Retrieves the default grid.
	 *
	 * @param   bool  $onlyID  whether or not only the id will be returned, defaults to true
	 *
	 * @return array|int int the id, otherwise the grid table entry as an array
	 */
	public static function getDefault($onlyID = true)
	{
		$query = Adapters\Database::getQuery();
		$query->select("*")->from('#__organizer_grids')->where('isDefault = 1');
		Adapters\Database::setQuery($query);

		return $onlyID ? Adapters\Database::loadInt() : Adapters\Database::loadAssoc();
	}

	/**
	 * Retrieves the grid property for the given grid.
	 *
	 * @param   int  $gridID  the grid id
	 *
	 * @return string string the grid json string on success, otherwise null
	 */
	public static function getGrid(int $gridID)
	{
		$table = new Tables\Grids();
		$table->load($gridID);

		return $table->grid ? $table->grid : '';
	}

	/**
	 * @inheritDoc
	 */
	public static function getResources()
	{
		$query = Adapters\Database::getQuery();
		$tag   = Languages::getTag();
		$query->select("*, name_$tag as name, isDefault")->from('#__organizer_grids')->order('name');
		Adapters\Database::setQuery($query);

		return Adapters\Database::loadAssocList();
	}

}
