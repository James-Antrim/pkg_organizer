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

/**
 * Provides general functions for campus access checks, data retrieval and display.
 */
class Categories extends Associated implements Selectable
{
	use Filtered;
	use Numbered;

	protected static $resource = 'category';

	/**
	 * Retrieves the groups associated with a category.
	 *
	 * @param $categoryID
	 *
	 * @return array
	 */
	public static function getGroups($categoryID)
	{
		$tag   = Languages::getTag();
		$query = Adapters\Database::getQuery();
		$query->select("id, code, name_$tag AS name")
			->from('#__organizer_groups AS g')
			->where("categoryID = $categoryID");
		Adapters\Database::setQuery($query);

		return Adapters\Database::loadAssocList();
	}

	/**
	 * Retrieves the selectable options for the resource.
	 *
	 * @param   string  $access  any access restriction which should be performed
	 *
	 * @return array the available options
	 */
	public static function getOptions($access = '')
	{
		$name    = Languages::getTag() === 'en' ? 'name_en' : 'name_de';
		$options = [];
		foreach (self::getResources($access) as $category)
		{
			if ($category['active'])
			{
				$options[] = HTML::_('select.option', $category['id'], $category[$name]);
			}
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
	public static function getProgramName(int $categoryID)
	{
		$noName = Languages::_('ORGANIZER_NO_PROGRAM');
		if (!$categoryID)
		{
			return $noName;
		}

		$query = Adapters\Database::getQuery(true);
		$query->select('DISTINCT id')->from('#__organizer_programs')->where("categoryID = $categoryID");
		Adapters\Database::setQuery($query);

		if ($programIDs = Adapters\Database::loadIntColumn())
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
		$order = Languages::getTag() === 'en' ? 'name_en' : 'name_de';
		$query = Adapters\Database::getQuery(true);
		$query->select('DISTINCT c.*')->from('#__organizer_categories AS c')->order($order);

		if (!empty($access))
		{
			self::addAccessFilter($query, $access, 'category', 'c');
		}

		self::addOrganizationFilter($query, 'category', 'c');

		Adapters\Database::setQuery($query);

		return Adapters\Database::loadAssocList();
	}
}
