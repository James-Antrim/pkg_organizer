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
		$personID = $personID ? $personID : Persons::getIDByUserID(Users::getID());

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('COUNT(*)')
			->from('#__organizer_event_coordinators')
			->where("personID = $personID");

		if ($eventID)
		{
			$query->where("eventID = $eventID");
		}

		$dbo->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('loadResult', false);
	}

	/**
	 * Looks up the names of categories (or programs) associated with an event.
	 *
	 * @param   int  $eventID the id of the event
	 *
	 * @return array the names of the categories (or programs)
	 */
	public static function getCategoryNames(int $eventID)
	{
		$names = [];
		$dbo   = Factory::getDbo();
		$tag   = Languages::getTag();

		$query     = $dbo->getQuery(true);
		$nameParts = ["p.name_$tag", "' ('", 'd.abbreviation', "' '", 'p.accredited', "')'"];
		$query->select("c.name_$tag AS category, " . $query->concatenate($nameParts, "") . ' AS program')
			->select('c.id')
			->from('#__organizer_categories AS c')
			->innerJoin('#__organizer_groups AS g ON g.categoryID = c.id')
			->innerJoin('#__organizer_instance_groups AS ig ON ig.groupID = g.id')
			->innerJoin('#__organizer_instance_persons AS ip ON ip.id = ig.assocID')
			->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
			->leftJoin('#__organizer_programs AS p ON p.categoryID = ppr.id')
			->leftJoin('#__organizer_degrees AS d ON p.degreeID = d.id')
			->where("i.eventID = $eventID");

		$dbo->setQuery($query);

		if (!$results = OrganizerHelper::executeQuery('loadAssocList', []))
		{
			return [];
		}

		foreach ($results as $result)
		{
			$names[$result['id']] = empty($result['program']) ? $result['category'] : $result['program'];
		}

		return $names;
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
		$personID = $personID ? $personID : Persons::getIDByUserID(Users::getID());

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
