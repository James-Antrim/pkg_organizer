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
 * Provides general function for data retrieval and display.
 */
class Units extends ResourceHelper
{
	const TEACHER = 1;

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
		$tag   = Languages::getTag();
		$query = Database::getQuery();
		$query->select("g.id AS groupID, g.categoryID, g.fullName_$tag AS fqGroup, g.name_$tag AS nqGroup")
			->from('#__organizer_instances AS i')
			->innerJoin('#__organizer_instance_persons AS ip ON ip.instanceID = i.id')
			->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ip.id')
			->innerJoin('#__organizer_groups AS g ON g.id = ig.groupID')
			->where("i.eventID = $eventID")
			->where("i.unitID = $unitID");
		Database::setQuery($query);

		return Database::loadAssocList('groupID');
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
		$query = Database::getQuery(true);

		$query->select('DISTINCT i.eventID')
			->from('#__organizer_instances AS i')
			->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
			->where("unitID = $unitID")
			->where("i.delta != 'removed'");
		Database::setQuery($query);

		return Database::loadIntColumn();
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
		$tag   = Languages::getTag();
		$query = Database::getQuery(true);
		$query->select("DISTINCT name_$tag")
			->from('#__organizer_events AS e')
			->innerJoin('#__organizer_instances AS i ON i.eventID = e.id')
			->where("i.unitID = $unitID");
		Database::setQuery($query);
		$eventNames = Database::loadColumn();

		return $glue ? implode($glue, $eventNames) : $eventNames;
	}

	/**
	 * Check if person is associated with a unit as a teacher.
	 *
	 * @param   int  $unitID    the optional id of the unit
	 * @param   int  $personID  the optional id of the person
	 *
	 * @return bool true if the person is a unit teacher, otherwise false
	 */
	public static function teaches($unitID = 0, $personID = 0)
	{
		$personID = $personID ? $personID : Persons::getIDByUserID(Users::getID());

		$query = Database::getQuery(true);
		$query->select('COUNT(*)')
			->from('#__organizer_instance_persons AS ip')
			->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
			->where("ip.personID = $personID")
			->where('ip.roleID = ' . self::TEACHER);

		if ($unitID)
		{
			$query->where("i.unitID = $unitID");
		}

		Database::setQuery($query);

		return Database::loadBool();
	}
}