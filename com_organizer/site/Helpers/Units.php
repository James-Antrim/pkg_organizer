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
 * Provides general function for data retrieval and display.
 */
class Units extends ResourceHelper
{
	/**
	 * Retrieves the group/category contexts for a given unit/event tub
	 *
	 * @param   int  $unitID   the unit id
	 * @param   int  $eventID  the event id
	 *
	 * @return mixed|null
	 */
	public static function getContexts($unitID, $eventID)
	{
		$dbo   = Factory::getDbo();
		$tag   = Languages::getTag();
		$query = $dbo->getQuery(true);
		$query->select("g.id AS groupID, g.categoryID, g.fullName_$tag AS fqGroup, g.name_$tag AS nqGroup")
			->from('#__organizer_instances AS i')
			->innerJoin('#__organizer_instance_persons AS ip ON ip.instanceID = i.id')
			->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ip.id')
			->innerJoin('#__organizer_groups AS g ON g.id = ig.groupID')
			->where("i.eventID = $eventID")
			->where("i.unitID = $unitID");

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', [], 'groupID');
	}

	/**
	 * Retrieves the id of events associated with the resource
	 *
	 * @param   int  $unitID  the id of the resource for which the associated events are requested
	 *
	 * @return array the ids of events associated with the resource
	 */
	public static function getEventIDs($unitID)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('DISTINCT i.eventID')
			->from('#__organizer_instances AS i')
			->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
			->where("unitID = $unitID")
			->where("i.delta != 'removed'");

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadColumn', []);
	}

	/**
	 * Gets a list of distinct names associated with the unit, optionally converted to a string for later display output.
	 *
	 * @param   int     $unitID  the id of the unit
	 * @param   string  $glue    the string to use to concatenate associated names
	 *
	 * @return array|string the names of the associated events
	 */
	public static function getEventNames($unitID, $glue = '')
	{
		$dbo   = Factory::getDbo();
		$tag   = Languages::getTag();
		$query = $dbo->getQuery(true);
		$query->select("DISTINCT name_$tag")
			->from('#__organizer_events AS e')
			->innerJoin('#__organizer_instances AS i ON i.eventID = e.id')
			->where("i.unitID = $unitID");
		$dbo->setQuery($query);

		$eventNames = OrganizerHelper::executeQuery('loadColumn', []);

		return $glue ? implode($glue, $eventNames) : $eventNames;
	}

	/**
	 * Check if person is associated with a unit as a teacher.
	 *
	 * @param   int  $unitID    the optional id of the unit
	 * @param   int  $personID  the optional id of the person
	 *
	 * @return boolean true if the person is a unit teacher, otherwise false
	 */
	public static function teaches($unitID = 0, $personID = 0)
	{
		if (!$personID)
		{
			$user     = Factory::getUser();
			$personID = Persons::getIDByUserID($user->id);
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('COUNT(*)')
			->from('#__organizer_instance_persons AS ip')
			->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
			->where("ip.personID = $personID")
			->where('ip.roleID = ' . self::TEACHER);

		if ($unitID)
		{
			$query->where("i.unitID = '$unitID'");
		}

		$dbo->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('loadResult', false);
	}
}