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
 * Provides general functions for campus access checks, data retrieval and display.
 */
class Categories extends Associated implements Selectable
{
	use Filtered;

	static protected $resource = 'category';

	/**
	 * Retrieves the selectable options for the resource.
	 *
	 * @param   string  $access  any access restriction which should be performed
	 *
	 * @return array the available options
	 */
	public static function getOptions($access = '')
	{
		$name = Languages::getTag() === 'en' ? 'name_en' : 'name_de';
		$options = [];
		foreach (self::getResources($access) as $category)
		{
			$options[] = HTML::_('select.option', $category['id'], $category[$name]);
		}

		uasort($options, function ($optionOne, $optionTwo) {
			return $optionOne->text > $optionTwo->text;
		});

		// Any out of sequence indexes cause JSON to treat this as an object
		return array_values($options);
	}

	/**
	 * Retrieves the name of the program associated with the category.
	 *
	 * @param   int  $categoryID  the table id for the program
	 *
	 * @return string the name of the (plan) program, otherwise empty
	 */
	public static function getProgramName($categoryID)
	{
		$noName = Languages::_('ORGANIZER_NO_PROGRAM');
		if (!$categoryID)
		{
			return $noName;
		}

		$dbo = Factory::getDbo();

		$query = $dbo->getQuery(true);
		$query->select('DISTINCT id')->from('#__organizer_programs')->where("categoryID = $categoryID");
		$dbo->setQuery($query);

		if ($programIDs = OrganizerHelper::executeQuery('loadColumn', []))
		{
			return count($programIDs) > 1 ?
				Languages::_('ORGANIZER_MULTIPLE_PROGRAMS') : Programs::getName($programIDs[0]);
		}

		return $noName;
	}

	/**
	 * Retrieves the resource items.
	 *
	 * @param   string  $access  any access restriction which should be performed
	 *
	 * @return array the available resources
	 */
	public static function getResources($access = '')
	{
		$dbo   = Factory::getDbo();
		$order = Languages::getTag() === 'en' ? 'name_en' : 'name_de';

		$query = $dbo->getQuery(true);
		$query->select('*')->from('#__organizer_categories AS c')->order($order);

		if (!empty($access))
		{
			self::addAccessFilter($query, $access, 'category', 'c');
		}

		self::addOrganizationFilter($query, 'category', 'c');

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
	}
}
