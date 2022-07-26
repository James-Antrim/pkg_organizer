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
use Organizer\Tables;

/**
 * Provides general functions for course access checks, data retrieval and display.
 */
class Courses extends ResourceHelper
{
	// RoleIDs
	private const TEACHER = 1, TUTOR = 2, SUPERVISOR = 3, SPEAKER = 4;

	/**
	 * Check if the user is a course coordinator.
	 *
	 * @param   int  $courseID  the optional id of the course
	 * @param   int  $personID  the optional id of the person entry
	 *
	 * @return bool true if the user is a coordinator, otherwise false
	 */
	public static function coordinates(int $courseID = 0, int $personID = 0): bool
	{
		if (Can::administrate())
		{
			return true;
		}

		if (!$personID = $personID ?: Persons::getIDByUserID())
		{
			return false;
		}

		$query = Database::getQuery();
		$query->select('COUNT(*)')
			->from('#__organizer_event_coordinators AS ec')
			->where("ec.personID = $personID");

		if ($courseID)
		{
			$query->innerJoin('#__organizer_events AS e ON e.id = ec.eventID')
				->innerJoin('#__organizer_instances AS i ON i.eventID = e.id')
				->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
				->where("u.courseID = $courseID");
		}

		Database::setQuery($query);

		return Database::loadBool();
	}

	/**
	 * Retrieves the campus id associated with the course.
	 *
	 * @param   int  $courseID  the id of the course
	 *
	 * @return int  the id of the associated campus
	 */
	public static function getCampusID(int $courseID): int
	{
		$course = new Tables\Courses();

		return $course->load($courseID) ? $course->campusID : 0;
	}

	/**
	 * Creates a display of formatted dates for a course
	 *
	 * @param   int  $courseID  the id of the course to be loaded
	 *
	 * @return string the dates to display
	 */
	public static function getDateDisplay(int $courseID): string
	{
		if ($dates = self::getDates($courseID))
		{
			return Dates::getDisplay($dates['startDate'], $dates ['endDate']);
		}

		return '';
	}

	/**
	 * Gets the course start and end dates.
	 *
	 * @param   int  $courseID  id of course to be loaded
	 *
	 * @return string[]  the start and end date for the given course
	 */
	public static function getDates(int $courseID = 0): array
	{
		if (empty($courseID))
		{
			return [];
		}

		$query = Database::getQuery();
		$query->select('DISTINCT MIN(startDate) AS startDate, MAX(endDate) AS endDate')
			->from('#__organizer_units')
			->where("courseID = $courseID");
		Database::setQuery($query);

		return Database::loadAssoc();
	}

	/**
	 * Retrieves events associated with the given course.
	 *
	 * @param   int  $courseID  the id of the course
	 *
	 * @return array[] the events associated with the course
	 */
	public static function getEvents(int $courseID): array
	{
		$tag   = Languages::getTag();
		$query = Database::getQuery();
		$query->select("DISTINCT e.id, e.name_$tag AS name, contact_$tag AS contact")
			->select("courseContact_$tag AS courseContact, content_$tag AS content, e.description_$tag AS description")
			->select("organization_$tag AS organization, pretests_$tag AS pretests, preparatory")
			->from('#__organizer_events AS e')
			->innerJoin('#__organizer_instances AS i ON i.eventID = e.id')
			->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
			->where("u.courseID = $courseID")
			->order('name ASC');
		Database::setQuery($query);

		if (!$events = Database::loadAssocList())
		{
			return [];
		}

		foreach ($events as &$event)
		{
			$event['speakers'] = self::getPersons($courseID, $event['id'], [self::SPEAKER]);
			$event['teachers'] = self::getPersons($courseID, $event['id'], [self::TEACHER]);
			$event['tutors']   = self::getPersons($courseID, $event['id'], [self::TUTOR]);
		}

		return $events;
	}

	/**
	 * Gets an array of participant IDs for a given course, optionally filtered by the participant's status
	 *
	 * @param   int  $courseID  the course id
	 *
	 * @return array[] list of participants in course
	 */
	public static function getGroupedParticipation(int $courseID): array
	{
		if (empty($courseID))
		{
			return [];
		}

		$query = Database::getQuery();
		$tag   = Languages::getTag();
		$query->select("pr.id, pr.name_$tag AS program, pr.accredited AS year, COUNT(*) AS participants")
			->select("d.abbreviation AS degree")
			->from('#__organizer_programs AS pr')
			->innerJoin('#__organizer_degrees AS d ON d.id = pr.degreeID')
			->innerJoin('#__organizer_participants AS pa ON pa.programID = pr.id')
			->innerJoin('#__organizer_course_participants AS cp ON cp.participantID = pa.id')
			->where("courseID = $courseID")
			->order("pr.name_$tag, d.abbreviation, pr.accredited DESC")
			->group("pr.id");
		Database::setQuery($query);

		if (!$programCounts = Database::loadAssocList())
		{
			return $programCounts;
		}

		$results = [];

		foreach ($programCounts as $programCount)
		{
			$organizationIDs = Programs::getOrganizationIDs($programCount['id']);
			foreach ($organizationIDs as $organizationID)
			{
				$organization = Organizations::getFullName($organizationID);

				if (empty($results[$organization]))
				{
					$results[$organization] = [
						'participants'      => $programCount['participants'],
						$programCount['id'] => $programCount
					];
				}
				else
				{
					$results[$organization]['participants']
						= $results[$organization]['participants'] + $programCount['participants'];
				}

				$results[$organization][$programCount['id']] = $programCount;
			}
		}

		ksort($results);

		return $results;
	}

	/**
	 * Gets instances associated with the given course.
	 *
	 * @param   int  $courseID  the id of the course
	 *
	 * @return int[] the instances which are a part of the course
	 */
	public static function getInstanceIDs(int $courseID): array
	{
		$query = Database::getQuery();
		$query->select("DISTINCT i.id")
			->from('#__organizer_instances AS i')
			->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
			->where("u.courseID = $courseID")
			->order('i.id');
		Database::setQuery($query);

		return Database::loadIntColumn();
	}

	/**
	 * Gets an array of participant IDs for a given course, optionally filtered by the participant's status
	 *
	 * @param   int       $courseID  the course id
	 * @param   int|null  $status    the participant status
	 *
	 * @return int [] the participant IDs
	 */
	public static function getParticipantIDs(int $courseID, int $status = null): array
	{
		if (empty($courseID))
		{
			return [];
		}

		$query = Database::getQuery();
		$query->select('participantID')
			->from('#__organizer_course_participants')
			->where("courseID = $courseID")
			->order('participantID');

		if ($status !== null and is_numeric($status))
		{
			$query->where("status = $status");
		}

		Database::setQuery($query);

		return Database::loadIntColumn();
	}

	/**
	 * Gets persons associated with the given course, optionally filtered by event and role.
	 *
	 * @param   int    $courseID  the id of the course
	 * @param   int    $eventID   the id of the event
	 * @param   array  $roleIDs   the id of the roles the persons should have
	 *
	 * @return string[] the persons matching the search criteria
	 */
	public static function getPersons(int $courseID, int $eventID = 0, array $roleIDs = []): array
	{
		$query = Database::getQuery();
		$query->select("DISTINCT ip.personID")
			->from('#__organizer_instance_persons AS ip')
			->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
			->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
			->where("u.courseID = $courseID");

		if ($eventID)
		{
			$query->where("i.eventID = $eventID");
		}

		if ($roleIDs)
		{
			$query->where('ip.roleID IN (' . implode(',', $roleIDs) . ')');
		}

		Database::setQuery($query);
		if (!$personIDs = Database::loadIntColumn())
		{
			return [];
		}

		$persons = [];
		foreach ($personIDs as $personID)
		{
			$persons[$personID] = Persons::getLNFName($personID);
		}

		return $persons;
	}

	/**
	 * Retrieves the ids of units associated with the course.
	 *
	 * @param   int  $courseID  the id of the course with which the units must be associated
	 *
	 * @return int[] the ids of the associated units
	 */
	public static function getUnitIDs(int $courseID): array
	{
		$query = Database::getQuery();
		$query->select('DISTINCT id')->from('#__organizer_units')->where("courseID = $courseID");
		Database::setQuery($query);

		return Database::loadIntColumn();
	}

	/**
	 * Check if user has a course responsibility.
	 *
	 * @param   int  $courseID  the optional id of the course
	 * @param   int  $personID  the optional id of the person
	 * @param   int  $roleID    the optional if of the person's role
	 *
	 * @return bool true if the user has a course responsibility, otherwise false
	 */
	public static function hasResponsibility(int $courseID = 0, int $personID = 0, int $roleID = 0): bool
	{
		if (Can::administrate())
		{
			return true;
		}

		if (!$personID = $personID ?: Persons::getIDByUserID(Users::getID()))
		{
			return false;
		}

		$query = Database::getQuery();
		$query->select('COUNT(*)')
			->from('#__organizer_instance_persons AS ip')
			->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
			->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
			->where("ip.personID = $personID");

		if ($courseID)
		{
			$query->where("u.courseID = $courseID");
		}

		if ($roleID)
		{
			$query->where("ip.roleID = $roleID");
		}

		Database::setQuery($query);

		return Database::loadBool();
	}

	/**
	 * Checks if the course is expired
	 *
	 * @param   int  $courseID  the id of the course
	 *
	 * @return bool true if the course is expired, otherwise false
	 */
	public static function isExpired(int $courseID): bool
	{
		if ($dates = self::getDates($courseID))
		{
			return date('Y-m-d') > $dates['endDate'];
		}

		return true;
	}

	/**
	 * Checks if the number of active participants is less than the number of max participants
	 *
	 * @param   int  $courseID  the id of the course
	 *
	 * @return bool true if the course is full, otherwise false
	 */
	public static function isFull(int $courseID): bool
	{
		$table = new Tables\Courses();
		if (!$table->load($courseID) or !$maxParticipants = $table->maxParticipants)
		{
			return false;
		}

		$query = Database::getQuery();
		$query->select('COUNT(*)')
			->from('#__organizer_course_participants')
			->where("courseID = $courseID")
			->where('status = 1');
		Database::setQuery($query);

		return Database::loadInt() >= $maxParticipants;
	}

	/**
	 * Checks if the course is a preparatory course.
	 *
	 * @param   int  $courseID  the id of the course
	 *
	 * @return bool true if the course is expired, otherwise false
	 */
	public static function isPreparatory(int $courseID): bool
	{
		$query = Database::getQuery();
		$query->select('COUNT(*)')
			->from('#__organizer_units AS u')
			->innerJoin('#__organizer_instances AS i ON i.unitID = u.id')
			->innerJoin('#__organizer_events AS e ON e.id = i.eventID')
			->where("u.courseID = $courseID")
			->where('e.preparatory = 1');

		Database::setQuery($query);

		return Database::loadBool();
	}

	/**
	 * Check if user is a speaker.
	 *
	 * @param   int  $courseID  the optional id of the course
	 * @param   int  $personID  the optional id of the person
	 *
	 * @return bool true if the user is a speaker, otherwise false
	 */
	public static function speaks(int $courseID = 0, int $personID = 0): bool
	{
		return self::hasResponsibility($courseID, $personID, self::SPEAKER);
	}

	/**
	 * Check if user a course supervisor.
	 *
	 * @param   int  $courseID  the optional id of the course
	 * @param   int  $personID  the optional id of the person
	 *
	 * @return bool true if the user is a supervisor, otherwise false
	 */
	public static function supervises(int $courseID = 0, int $personID = 0): bool
	{
		return self::hasResponsibility($courseID, $personID, self::SUPERVISOR);
	}

	/**
	 * Check if user is a course teacher.
	 *
	 * @param   int  $courseID  the optional id of the course
	 * @param   int  $personID  the optional id of the person
	 *
	 * @return bool true if the user is a course teacher, otherwise false
	 */
	public static function teaches(int $courseID = 0, int $personID = 0): bool
	{
		return self::hasResponsibility($courseID, $personID, self::TEACHER);
	}

	/**
	 * Check if user is a course tutor.
	 *
	 * @param   int  $courseID  the optional id of the course
	 * @param   int  $personID  the optional id of the person
	 *
	 * @return bool true if the user is a tutor, otherwise false
	 */
	public static function tutors(int $courseID = 0, int $personID = 0): bool
	{
		return self::hasResponsibility($courseID, $personID, self::TUTOR);
	}
}
