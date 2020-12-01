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
class CourseParticipants extends ResourceHelper
{
	private const UNREGISTERED = null;

	/**
	 * Determines whether or not the participant has paid for the course.
	 *
	 * @param   int  $courseID       the course id
	 * @param   int  $participantID  the participant id
	 *
	 * @return  mixed int if the user has a course participant state, otherwise null
	 */
	public static function hasPaid(int $courseID, int $participantID)
	{
		$course = new Tables\Courses();

		if (!$course->load($courseID))
		{
			return false;
		}
		elseif (empty($course->fee))
		{
			return true;
		}

		$courseParticipant = new Tables\CourseParticipants();

		if (!$courseParticipant->load(['courseID' => $courseID, 'participantID' => $participantID]))
		{
			return false;
		}

		return (bool) $courseParticipant->paid;
	}

	/**
	 * Retrieves the participant's state for the given course
	 *
	 * @param   int  $courseID       the course id
	 * @param   int  $eventID        the id of the specific course event
	 * @param   int  $participantID  the id of the participant
	 *
	 * @return  int|null int if the user has a course participant state, otherwise null
	 */
	public static function getState(int $courseID, int $participantID, $eventID = 0)
	{
		$query = Database::getQuery();
		$query->select('status')
			->from('#__organizer_course_participants AS cp')
			->where("cp.courseID = $courseID")
			->where("cp.participantID = $participantID");

		if ($eventID)
		{
			$query->innerJoin('#__organizer_units AS u ON u.courseID = cp.courseID')
				->innerJoin('#__organizer_instances AS i ON i.unitID = u.id')
				->innerJoin('#__organizer_instance_participants AS ip ON ip.instanceID = i.id')
				->where("i.eventID = $eventID")
				->where("ip.participantID = $participantID");
		}

		Database::setQuery($query);
		$state = Database::loadResult();

		if ($state === self::UNREGISTERED)
		{
			return $state;
		}

		return (int) $state;
	}

	/**
	 * Checks whether all the necessary participant information has been entered.
	 *
	 * @param   int  $courseID       the id of the course to check against
	 * @param   int  $participantID  the id of the participant to validate
	 *
	 * @return bool true if the participant entry is incomplete, otherwise false
	 */
	public static function validProfile(int $courseID, int $participantID)
	{
		$participant = new Tables\Participants();
		if (empty($participantID) or !$participant->load($participantID))
		{
			return false;
		}

		if (Courses::isPreparatory($courseID))
		{
			$requiredProperties = ['address', 'city', 'forename', 'programID', 'surname', 'zipCode'];
		}
		// Resolve any other contexts here later.
		else
		{
			$requiredProperties = [];
		}

		foreach ($requiredProperties as $property)
		{
			if (empty($participant->get($property)))
			{
				return false;
			}
		}

		return true;
	}
}
