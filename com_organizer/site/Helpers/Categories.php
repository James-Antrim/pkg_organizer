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
class Categories extends ResourceHelper implements Associated, Selectable
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
		if (!$categoryID)
		{
			return $noName;
		}

		$dbo = Factory::getDbo();

		$query = $dbo->getQuery(true);
		$query->select('DISTINCT id')->from('#__organizer_programs')->where("categoryID = '$categoryID'");
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
