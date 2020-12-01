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
class Methods extends ResourceHelper implements Selectable
{
	/**
	 * @inheritDoc
	 */
	public static function getOptions()
	{
		$options = [];
		foreach (self::getResources() as $method)
		{
			$options[] = HTML::_('select.option', $method['id'], $method['name']);
		}

		return $options;
	}

	/**
	 * @inheritDoc
	 */
	public static function getResources()
	{
		$query = Database::getQuery();
		$tag   = Languages::getTag();
		$query->select("DISTINCT m.*, m.name_$tag AS name")
			->from('#__organizer_methods AS m')
			->innerJoin('#__organizer_instances AS i ON i.methodID = m.id')
			->order('name');
		Database::setQuery($query);

		return Database::loadAssocList();
	}
}
