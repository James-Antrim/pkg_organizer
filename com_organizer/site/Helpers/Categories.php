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
class Categories implements Associated, Selectable
{
	use Filtered;

	/**
	 * Retrieves the ids of organizations associated with the resource
	 *
	 * @param   int  $resourceID  the id of the resource for which the associated organizations are requested
	 *
	 * @return array the ids of organizations associated with the resource
	 */
	public static function getOrganizationIDs($resourceID)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('organizationID')
			->from('#__organizer_associations')
			->where("categoryID = $resourceID");
		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadColumn', []);
	}

	/**
	 * Retrieves the category name
	 *
	 * @param   int  $categoryID  the table id for the program
	 *
	 * @return string the name of the (plan) program, otherwise empty
	 */
	public static function getName($categoryID)
	{
		$dbo = Factory::getDbo();
		$tag = Languages::getTag();

		$query     = $dbo->getQuery(true);
		$nameParts = ["p.name_$tag", "' ('", 'd.abbreviation', "' '", 'p.accredited', "')'"];
		$query->select('cat.name AS catName, ' . $query->concatenate($nameParts, "") . ' AS name');

		$query->from('#__organizer_categories AS cat');
		$query->leftJoin('#__organizer_programs AS p ON p.categoryID = cat.id');
		$query->leftJoin('#__organizer_degrees AS d ON d.id = p.degreeID');
		$query->where("cat.id = '$categoryID'");

		$dbo->setQuery($query);
		$names = OrganizerHelper::executeQuery('loadAssoc', []);

		return empty($names) ? '' : empty($names['name']) ? $names['catName'] : $names['name'];
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
		$options = [];
		foreach (self::getResources($access) as $category)
		{
			$name = empty($category['programName']) ? $category['name'] : $category['programName'];

			$options[] = HTML::_('select.option', $category['id'], $name);
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
	public static function getProgram($categoryID)
	{
		$noName = Languages::_('ORGANIZER_NO_PROGRAM');
		if (empty($categoryID))
		{
			return $noName;
		}

		$dbo = Factory::getDbo();
		$tag = Languages::getTag();

		$query     = $dbo->getQuery(true);
		$nameParts = ["p.name_$tag", "' ('", 'd.abbreviation', "' '", 'p.accredited', "')'"];
		$query->select($query->concatenate($nameParts, "") . ' AS name')
			->from('#__organizer_programs AS p')
			->innerJoin('#__organizer_degrees AS d ON d.id = p.degreeID')
			->innerJoin('#__organizer_categories AS cat ON cat.id = p.categoryID')
			->where("p.categoryID = '$categoryID'")
			->order('p.accredited DESC');


		$dbo->setQuery($query);
		$names = OrganizerHelper::executeQuery('loadColumn', []);

		return empty($names) ? $noName : $names[0];
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
		$dbo = Factory::getDbo();
		$tag = Languages::getTag();

		$query     = $dbo->getQuery(true);
		$nameParts = ["p.name_$tag", "' ('", 'd.abbreviation', "' '", 'p.accredited', "')'"];
		$query->select('DISTINCT c.*, ' . $query->concatenate($nameParts, "") . ' AS programName')
			->from('#__organizer_categories AS c')
			->leftJoin('#__organizer_programs AS p ON p.categoryID = c.id')
			->leftJoin('#__organizer_degrees AS d ON d.id = p.degreeID')
			->order('c.name');

		if (!empty($access))
		{
			$query->innerJoin('#__organizer_associations AS a ON a.categoryID = c.id');
			self::addAccessFilter($query, 'a', $access);
		}

		self::addOrganizationFilter($query, 'category', 'c');

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
	}
}