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
use Organizer\Tables;

/**
 * Class provides general functions for retrieving building data.
 */
class Grids extends ResourceHelper
{
	/**
	 * Retrieves the selectable options for the resource.
	 *
	 * @return array the available options
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
	 * @return mixed
	 */
	public static function getDefault($onlyID = true)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select("*")->from('#__organizer_grids')->where('isDefault = 1');

		$dbo->setQuery($query);

		return $onlyID ?
			OrganizerHelper::executeQuery('loadResult', []) : OrganizerHelper::executeQuery('loadAssoc', []);
	}

	/**
	 * Retrieves the grid property for the given grid.
	 *
	 * @param   int  $gridID  the grid id
	 *
	 * @return mixed string the grid json string on success, otherwise null
	 */
	public static function getGrid($gridID)
	{
		$table = new Tables\Grids();

		if ($table->load($gridID) and $grid = $table->grid)
		{
			return $grid;
		}

		return '';
	}

	/**
	 * Retrieves the resource items.
	 *
	 * @return array the available resources
	 */
	public static function getResources()
	{
		$tag = Languages::getTag();

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select("*, name_$tag as name, isDefault")->from('#__organizer_grids')->order('name');

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
	}

}
