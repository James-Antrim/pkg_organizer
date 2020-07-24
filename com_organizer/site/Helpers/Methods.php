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

use Joomla\CMS\Factory;

/**
 * Provides general functions for room type access checks, data retrieval and display.
 */
class Methods extends ResourceHelper implements Selectable
{
	/**
	 * Retrieves a list of resources in the form of name => id.
	 *
	 * @return array the resources, or empty
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
	 * Retrieves the resource items.
	 *
	 * @return array the available resources
	 */
	public static function getResources()
	{
		$dbo = Factory::getDbo();
		$tag = Languages::getTag();

		$query = $dbo->getQuery(true);
		$query->select("DISTINCT m.*, m.name_$tag AS name")
			->from('#__organizer_methods AS m')
			->innerJoin('#__organizer_instances AS i ON i.methodID = m.id')
			->order('name');
		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
	}
}
