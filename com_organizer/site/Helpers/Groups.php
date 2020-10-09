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
class Groups extends Associated implements Selectable
{
	use Filtered, Planned;

	static protected $resource = 'group';

	/**
	 * Retrieves the events associated with a group.
	 *
	 * @param $groupID
	 *
	 * @return array
	 */
	public static function getEvents($groupID)
	{
		$dbo   = Factory::getDbo();
		$tag   = Languages::getTag();
		$query = $dbo->getQuery(true);
		$query->select("DISTINCT e.id, e.code, e.name_$tag AS name, e.description_$tag AS description")
			->from('#__organizer_events AS e')
			->innerJoin('#__organizer_instances AS i ON i.eventID = e.id')
			->innerJoin('#__organizer_instance_persons AS ip ON ip.instanceID = i.id')
			->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ip.id')
			->where("groupID = $groupID");
		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
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
		$categoryID  = Input::getInt('categoryID');
		$categoryIDs = $categoryID ? [$categoryID] : Input::getFilterIDs('category');
		$tag         = Languages::getTag();
		$name        = count($categoryIDs) === 1 ? "name_$tag" : "fullName_$tag";
		$options     = [];

		foreach (self::getResources() as $group)
		{
			if ($group['active'])
			{
				$options[] = HTML::_('select.option', $group['id'], $group[$name]);
			}
		}

		uasort($options, function ($optionOne, $optionTwo) {
			return $optionOne->text > $optionTwo->text;
		});

		// Any out of sequence indexes cause JSON to treat this as an object
		return array_values($options);
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
		// TODO Remove (plan) programs on completion of migration.
		if ($categoryID = Input::getInt('programIDs') or $categoryID = Input::getInt('categoryID'))
		{
			$categoryIDs = [$categoryID];
		}

		$categoryIDs = empty($categoryIDs) ? Input::getIntCollection('categoryIDs') : $categoryIDs;
		$categoryIDs = empty($categoryIDs) ? Input::getFilterIDs('category') : $categoryIDs;

		// TODO Remove departments on completion of migration.
		$departmentID    = Input::getInt('departmentIDs');
		$organizationID  = Input::getInt('organizationID', $departmentID);
		$organizationIDs = $organizationID ? [$organizationID] : Input::getFilterIDs('organization');

		if (empty($categoryIDs) and empty($organizationIDs))
		{
			return [];
		}

		$dbo = Factory::getDbo();

		$query = $dbo->getQuery(true);
		$query->select('g.*');
		$query->from('#__organizer_groups AS g');

		if (!empty($access))
		{
			self::addAccessFilter($query, $access, 'group', 'g');
		}

		self::addOrganizationFilter($query, 'group', 'g');
		self::addResourceFilter($query, 'category', 'cat', 'g');

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
	}

	/**
	 * Retrieves the units associated with an event.
	 *
	 * @param   int     $groupID   the id of the referenced event
	 * @param   string  $date      the date context for the unit search
	 * @param   string  $interval  the interval to use as context for units
	 *
	 * @return array
	 */
	public static function getUnits($groupID, $date, $interval = 'term')
	{
		$dbo   = Factory::getDbo();
		$tag   = Languages::getTag();
		$query = $dbo->getQuery(true);
		$query->select("DISTINCT u.id, u.comment, m.abbreviation_$tag AS method, eventID")
			->from('#__organizer_units AS u')
			->innerJoin('#__organizer_instances AS i ON i.unitID = u.id')
			->innerJoin('#__organizer_instance_persons AS ip ON ip.instanceID = i.id')
			->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ip.id')
			->leftJoin('#__organizer_methods AS m ON m.id = i.methodID')
			->where("groupID = $groupID")
			->where("u.delta != 'removed'");

		self::addUnitDateRestriction($query, $date, $interval);

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
	}
}
