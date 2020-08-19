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
 * Provides general functions for subject access checks, data retrieval and display.
 */
class Events extends ResourceHelper
{
	use Planned;

	static protected $resource = 'event';

	/**
	 * Check if user is a subject coordinator.
	 *
	 * @param   int  $eventID   the optional id of the subject
	 * @param   int  $personID  the optional id of the person entry
	 *
	 * @return boolean true if the user is a coordinator, otherwise false
	 */
	public static function coordinates($eventID = 0, $personID = 0)
	{
		if (!$personID)
		{
			$user     = Factory::getUser();
			$personID = Persons::getIDByUserID($user->id);
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('COUNT(*)')
			->from('#__organizer_event_coordinators')
			->where("personID = $personID");

		if ($eventID)
		{
			$query->where("eventID = '$eventID'");
		}

		$dbo->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('loadResult', false);
	}

	/**
	 * Retrieves the units associated with an event.
	 *
	 * @param   int     $eventID   the id of the referenced event
	 * @param   string  $date      the date context for the unit search
	 * @param   string  $interval  the interval to use as context for units
	 *
	 * @return array
	 */
	public static function getUnits($eventID, $date, $interval = 'term')
	{
		$dbo   = Factory::getDbo();
		$tag   = Languages::getTag();
		$query = $dbo->getQuery(true);
		$query->select("DISTINCT u.id, u.comment, m.abbreviation_$tag AS method")
			->from('#__organizer_units AS u')
			->innerJoin('#__organizer_instances AS i ON i.unitID = u.id')
			->leftJoin('#__organizer_methods AS m ON m.id = i.methodID')
			->where("eventID = $eventID");

		self::addUnitDateRestriction($query, $date, $interval);

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
	}

	/**
	 * Check if user is a subject teacher.
	 *
	 * @param   int  $eventID   the optional id of the subject
	 * @param   int  $personID  the optional id of the person entry
	 *
	 * @return boolean true if the user is a teacher, otherwise false
	 */
	public static function teaches($eventID = 0, $personID = 0)
	{
		if (!$personID)
		{
			$user     = Factory::getUser();
			$personID = Persons::getIDByUserID($user->id);
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('COUNT(*)')
			->from('#__organizer_instances AS i')
			->innerJoin('#__organizer_instance_persons AS ip ON ip.instanceID = i.id')
			->where("ip.personID = $personID")
			->where("ip.roleID = 1");

		if ($eventID)
		{
			$query->where("i.eventID = '$eventID'");
		}

		$dbo->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('loadResult', false);
	}
}
