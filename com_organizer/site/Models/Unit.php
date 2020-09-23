<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored unit data.
 */
class Unit extends BaseModel
{
	/**
	 * Creates a course based on the information associated with the given unit.
	 *
	 * @return int the id of the newly created course
	 */
	public function addCourse()
	{
		$unit = new Tables\Units();
		if (!$unitID = Helpers\Input::getSelectedID() or !$unit->load($unitID))
		{
			return false;
		}

		if ($unit->courseID)
		{
			return $unit->courseID;
		}

		$authorized = Helpers\Can::scheduleTheseOrganizations();
		if (!in_array($unit->organizationID, $authorized))
		{
			Helpers\OrganizerHelper::error(403);
		}

		$event  = new Tables\Events();
		$course = new Tables\Courses();

		foreach ($eventIDs = Helpers\Units::getEventIDs($unitID) as $eventID)
		{
			$event->load($eventID);

			if ($course->name_de === null)
			{
				$course->name_de = $event->name_de;
			}
			elseif (!strpos($course->name_de, $event->name_de))
			{
				$course->name_de .= ' / ' . $event->name_de;
			}

			if ($course->name_en === null)
			{
				$course->name_en = $event->name_en;
			}
			elseif (!strpos($course->name_en, $event->name_en))
			{
				$course->name_en .= ' / ' . $event->name_en;
			}

			if ($course->deadline === null or $event->deadline < $course->deadline)
			{
				$course->deadline = $event->deadline;
			}

			if ($course->fee === null or $event->fee < $course->fee)
			{
				$course->fee = $event->fee;
			}

			if ($course->maxParticipants === null or $event->maxParticipants < $course->maxParticipants)
			{
				$course->maxParticipants = $event->maxParticipants;
			}

			if ($course->registrationType === null or $event->registrationType < $course->registrationType)
			{
				$course->registrationType = $event->registrationType;
			}
		}

		$course->campusID = $this->getCourseCampusID($unit, $event->campusID);
		$course->termID   = $unit->termID;

		if (!$course->store())
		{
			return 0;
		}

		$unit->courseID = $course->id;
		$unit->store();

		return $course->id;
	}

	/**
	 * Gets the campus id to associate with a course based on event documentation and planning data.
	 *
	 * @param   Tables\Units  $unit       the unit table
	 * @param   int           $defaultID  the id of an event associated with the unit
	 *
	 * @return int the id of the campus to associate with the course
	 */
	private function getCourseCampusID($unit, $defaultID)
	{
		if (property_exists($unit, 'campusID') and $campusID = $unit->campusID)
		{
			return $campusID;
		}

		$query = $this->_db->getQuery(true);
		$query->select('c.id AS campusID, c.parentID, COUNT(*) AS campusCount')
			->from('#__organizer_campuses AS c')
			->innerJoin('#__organizer_buildings AS b ON b.campusID = c.id')
			->innerJoin('#__organizer_rooms AS r ON r.buildingID = b.id')
			->innerJoin('#__organizer_instance_rooms AS ir ON ir.roomID = r.id')
			->innerJoin('#__organizer_instance_persons AS ip ON ip.id = ir.assocID')
			->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
			->where("i.unitID = $unit->id")
			->group('c.id')
			->order('campusCount DESC');
		$this->_db->setQuery($query);

		$plannedCampus = Helpers\OrganizerHelper::executeQuery('loadAssoc', []);

		if ($plannedCampus['campusID'] === $defaultID or $plannedCampus['parentID'] === $defaultID)
		{
			return $plannedCampus['campusID'];
		}

		return $defaultID;
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Units A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Units;
	}
}
