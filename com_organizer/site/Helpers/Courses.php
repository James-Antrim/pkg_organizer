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
use Organizer\Tables;

/**
 * Provides general functions for course access checks, data retrieval and display.
 */
class Courses extends ResourceHelper
{
	// RoleIDs
	const TEACHER = 1, TUTOR = 2, SUPERVISOR = 3, SPEAKER = 4;

	/**
	 * Check if the user is a course coordinator.
	 *
	 * @param   int  $courseID  the optional id of the course
	 * @param   int  $personID  the optional id of the person entry
	 *
	 * @return boolean true if the user is a coordinator, otherwise false
	 */
	public static function coordinates($courseID = 0, $personID = 0)
	{
		if (Can::administrate())
		{
			return true;
		}

		if (!$personID = $personID ? $personID : Persons::getIDByUserID())
		{
			return false;
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

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

		$dbo->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('loadResult', 0);
	}

	/**
	 * Retrieves the campus id associated with the course.
	 *
	 * @param   int  $courseID  the id of the course
	 *
	 * @return string the course capacity text
	 */
	public static function getCampusID($courseID)
	{
		$course = new Tables\Courses;

		return $course->load($courseID) ? $course->campusID : 0;
	}

	/**
	 * Generates a capacity text for active course participants.
	 *
	 * @param   int  $courseID  the id of the course
	 *
	 * @return string the course capacity text
	 */
	public static function getCapacityText($courseID)
	{
		$course = new Tables\Courses;

		if (!$course->load($courseID))
		{
			return '';
		}

		$max = $course->maxParticipants;

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('COUNT(DISTINCT participantID)')
			->from('#__organizer_course_participants')
			->where("courseID = $courseID")
			->where("status = 1");
		$dbo->setQuery($query);

		$current = OrganizerHelper::executeQuery('loadResult', 0);

		return "<span class=\"icon-user-check\"></span>$current/$max";
	}

	/**
	 * Creates a display of formatted dates for a course
	 *
	 * @param   int  $courseID  the id of the course to be loaded
	 *
	 * @return string the dates to display
	 */
	public static function getDateDisplay($courseID)
	{
		if ($dates = self::getDates($courseID))
		{
			$dates = Dates::getDisplay($dates['startDate'], $dates ['endDate']);
		}
		else
		{
			return '';
		}

		$runText = '';

		/*if (OrganizerHelper::getApplication()->isClient('administrator'))
		{
			$runText = self::getRunText($courseID) . '<br>';
		}*/

		return $runText . $dates;
	}

	/**
	 * Gets the course start and end dates.
	 *
	 * @param   int  $courseID  id of course to be loaded
	 *
	 * @return array  the start and end date for the given course
	 */
	public static function getDates($courseID = 0)
	{
		if (empty($courseID))
		{
			return [];
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('DISTINCT MIN(startDate) AS startDate, MAX(endDate) AS endDate')
			->from('#__organizer_units')
			->where("courseID = $courseID");
		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssoc', []);
	}

	/**
	 * Retrieves events associated with the given course.
	 *
	 * @param   int  $courseID  the id of the course
	 *
	 * @return array the events associated with the course
	 */
	public static function getEvents($courseID)
	{
		$dbo = Factory::getDbo();
		$tag = Languages::getTag();

		$query = $dbo->getQuery('true');
		$query->select("DISTINCT e.id, e.name_$tag AS name, contact_$tag AS contact")
			->select("courseContact_$tag AS courseContact, content_$tag AS content, e.description_$tag AS description")
			->select("organization_$tag AS organization, pretests_$tag AS pretests, preparatory")
			->from('#__organizer_events AS e')
			->innerJoin('#__organizer_instances AS i ON i.eventID = e.id')
			->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
			->where("u.courseID = $courseID")
			->order('name ASC');

		$dbo->setQuery($query);
		if (!$events = OrganizerHelper::executeQuery('loadAssocList', []))
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
	 * Gets persons associated with the given course, optionally filtered by event and role.
	 *
	 * @param   int  $courseID  the id of the course
	 *
	 * @return array the rooms in which the course takes place
	 */
	public static function getGroups($courseID)
	{
		$dbo = Factory::getDbo();

		$query = $dbo->getQuery('true');
		$query->select("DISTINCT g.code")
			->from('#__organizer_groups AS g')
			->innerJoin('#__organizer_instance_groups AS ig ON ig.groupID = g.id')
			->innerJoin('#__organizer_instance_persons AS ip ON ip.id = ig.assocID')
			->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
			->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
			->where("ig.delta != 'removed'")
			->where("ip.delta != 'removed'")
			->where("i.delta != 'removed'")
			->where("u.delta != 'removed'")
			->where("u.courseID = $courseID")
			->order('g.code');

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadColumn', []);
	}

	/**
	 * Gets instances associated with the given course.
	 *
	 * @param   int  $courseID  the id of the course
	 *
	 * @return array the instances which are a part of the course
	 */
	public static function getInstanceIDs($courseID)
	{
		$dbo = Factory::getDbo();

		$query = $dbo->getQuery('true');
		$query->select("DISTINCT i.id")
			->from('#__organizer_instances AS i')
			->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
			->where("u.courseID = $courseID")
			->order('i.id');

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadColumn', []);
	}

	/**
	 * Gets an array of participant IDs for a given course, optionally filtered by the participant's status
	 *
	 * @param   int  $courseID  the course id
	 * @param   int  $status    the participant status
	 *
	 * @return array list of participants in course
	 */
	public static function getParticipantIDs($courseID, $status = null)
	{
		if (empty($courseID))
		{
			return [];
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('participantID')
			->from('#__organizer_course_participants')
			->where("courseID = $courseID")
			->order('participantID ASC');

		if ($status !== null and is_numeric($status))
		{
			$query->where("status = $status");
		}

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadColumn', []);
	}

	/**
	 * Gets persons associated with the given course, optionally filtered by event and role.
	 *
	 * @param   int    $courseID  the id of the course
	 * @param   int    $eventID   the id of the event
	 * @param   array  $roleIDs   the id of the roles the persons should have
	 *
	 * @return array the persons matching the search criteria
	 */
	public static function getPersons($courseID, $eventID = 0, $roleIDs = [])
	{
		$dbo = Factory::getDbo();

		$query = $dbo->getQuery('true');
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

		$dbo->setQuery($query);
		if (!$personIDs = OrganizerHelper::executeQuery('loadColumn', []))
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
	 * Generates a text descriptive of the course run information.
	 *
	 * @param   int  $courseID  the id of the course
	 *
	 * @return string the course run text
	 */
	public static function getRunText($courseID)
	{
		$course = new Tables\Courses;

		if (!$course->load($courseID))
		{
			return '';
		}

		if (self::isPreparatory($courseID))
		{
			return Terms::getName(Terms::getNextID($course->termID));
		}

		$term = Terms::getName($course->termID);

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('DISTINCT runID')
			->from('#__organizer_units AS u')
			->where("courseID = $courseID");
		$dbo->setQuery($query);

		if (!$runIDs = OrganizerHelper::executeQuery('loadColumn', []) or COUNT($runIDs) > 1)
		{
			return $term;
		}

		$runName = Runs::getName($runIDs[0]);

		return $runName ? "$runName ($term)" : $term;

	}

	/**
	 * Retrieves the ids of units associated with the course.
	 *
	 * @param   int  $courseID  the id of the course with which the units must be associated
	 *
	 * @return array the ids of the associated units
	 */
	public static function getUnitIDs($courseID)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('DISTINCT id')->from('#__organizer_units')->where("courseID = $courseID");
		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadColumn', []);
	}

	/**
	 * Check if user has a course responsibility.
	 *
	 * @param   int  $courseID  the optional id of the course
	 * @param   int  $personID  the optional id of the person
	 * @param   int  $roleID    the optional if of the person's role
	 *
	 * @return boolean true if the user has a course responsibility, otherwise false
	 */
	public static function hasResponsibility($courseID = 0, $personID = 0, $roleID = 0)
	{
		if (Can::administrate())
		{
			return true;
		}

		if (empty($personID))
		{
			$user = Factory::getUser();
			if (!$personID = Persons::getIDByUserID($user->id))
			{
				return false;
			}
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

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

		$dbo->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('loadResult', 0);
	}

	/**
	 * Checks if the course is expired
	 *
	 * @param   int  $courseID  the id of the course
	 *
	 * @return bool true if the course is expired, otherwise false
	 */
	public static function isExpired($courseID)
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
	public static function isFull($courseID)
	{
		$table = new Tables\Courses;
		if (!$maxParticipants = $table->getProperty('maxParticipants', $courseID))
		{
			return false;
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('COUNT(*)')
			->from('#__organizer_course_participants')
			->where("courseID = $courseID")
			->where('status = 1');
		$dbo->setQuery($query);
		$count = OrganizerHelper::executeQuery('loadResult', 0);

		return $count >= $maxParticipants;
	}

	/**
	 * Checks if the course is a preparatory course.
	 *
	 * @param   int  $courseID  the id of the course
	 *
	 * @return bool true if the course is expired, otherwise false
	 */
	public static function isPreparatory($courseID)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('COUNT(*)')
			->from('#__organizer_units AS u')
			->innerJoin('#__organizer_instances AS i ON i.unitID = u.id')
			->innerJoin('#__organizer_events AS e ON e.id = i.eventID')
			->where("u.courseID = $courseID")
			->where('e.preparatory = 1');

		$dbo->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('loadResult', 0);
	}

	/**
	 * Checks whether the current user participates in the course.
	 *
	 * @param   int  $courseID  the id of the course
	 *
	 * @return bool true if the user is a course participant, otherwise false
	 */
	public static function participates($courseID)
	{
		$participantID = Users::getID();
		$table         = new Tables\CourseParticipants();

		return $table->load(['courseID' => $courseID, 'participantID' => $participantID]);
	}

	/**
	 * Check if user is a speaker.
	 *
	 * @param   int  $courseID  the optional id of the course
	 * @param   int  $personID  the optional id of the person
	 *
	 * @return boolean true if the user is a speaker, otherwise false
	 */
	public static function speaks($courseID = 0, $personID = 0)
	{
		return self::hasResponsibility($courseID, $personID, self::SPEAKER);
	}

	/**
	 * Check if user a course supervisor.
	 *
	 * @param   int  $courseID  the optional id of the course
	 * @param   int  $personID  the optional id of the person
	 *
	 * @return boolean true if the user is a supervisor, otherwise false
	 */
	public static function supervises($courseID = 0, $personID = 0)
	{
		return self::hasResponsibility($courseID, $personID, self::SUPERVISOR);
	}

	/**
	 * Check if user is a course teacher.
	 *
	 * @param   int  $courseID  the optional id of the course
	 * @param   int  $personID  the optional id of the person
	 *
	 * @return boolean true if the user is a course teacher, otherwise false
	 */
	public static function teaches($courseID = 0, $personID = 0)
	{
		return self::hasResponsibility($courseID, $personID, self::TEACHER);
	}

	/**
	 * Check if user is a course tutor.
	 *
	 * @param   int  $courseID  the optional id of the course
	 * @param   int  $personID  the optional id of the person
	 *
	 * @return boolean true if the user is a tutor, otherwise false
	 */
	public static function tutors($courseID = 0, $personID = 0)
	{
		return self::hasResponsibility($courseID, $personID, self::TUTOR);
	}

//    /**
//     * Might move users from state pending to accepted
//     *
//     * @param int $courseID lesson id of lesson where participants have to be moved up
//     *
//     * @return void
//     */
//    public static function refreshWaitList($courseID)
//    {
//        $canAccept = self::canAcceptParticipant($courseID);
//
//        if ($canAccept) {
//            $dbo   = Factory::getDbo();
//            $query = $dbo->getQuery(true);
//
//            $query->select('userID');
//            $query->from('#__organizer_user_lessons');
//            $query->where("lessonID = '$courseID' and status = '0'");
//            $query->order('status_date, user_date');
//
//            $dbo->setQuery($query);
//
//            $nextParticipantID = OrganizerHelper::executeQuery('loadResult');
//
//            if (!empty($nextParticipantID)) {
//                Participants::changeState($nextParticipantID, $courseID, 1);
//            }
//        }
//    }
}
