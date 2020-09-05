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

use Exception;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored course data.
 */
class Course extends BaseModel
{
	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Courses A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Courses;
	}

	/**
	 * Migrates the participants of a given course from the old structure to the new structure.
	 *
	 * @return bool
	 */
	public function migrateParticipants()
	{
		$courseID = Helpers\Input::getSelectedID();

		// Empty course
		if (!$unitIDs = Helpers\Courses::getUnitIDs($courseID))
		{
			return true;
		}

		$unitIDs = implode(', ', $unitIDs);
		$query   = $this->_db->getQuery(true);
		$query->select('*')->from('#__thm_organizer_user_lessons')->where("lessonID IN ($unitIDs)");
		$this->_db->setQuery($query);

		// No baggage for the unit
		if (!$participants = Helpers\OrganizerHelper::executeQuery('loadAssocList', []))
		{
			return true;
		}

		$cConditions   = 'c.schedule_date = b.date AND c.startTime = b.startTime AND c.endTime = b.endTime';
		$cConditions   .= ' AND c.lessonID = i.unitID';
		$ccmConditions = 'ccm.calendarID = c.id AND ccm.configurationID = lc.id';
		$lsConditions  = 'ls.subjectID = i.eventID AND ls.lessonID = i.unitID';
		$instanceQuery = $this->_db->getQuery(true);
		$instanceQuery->select('i.id')
			->from('#__organizer_instances AS i')
			->innerJoin("#__thm_organizer_lesson_subjects AS ls ON $lsConditions")
			->innerJoin('#__thm_organizer_lesson_configurations AS lc ON lc.lessonID = ls.id')
			->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
			->innerJoin("#__thm_organizer_calendar AS c ON $cConditions")
			->innerJoin("#__thm_organizer_calendar_configuration_map AS ccm ON $ccmConditions");

		$deleteQuery = $this->_db->getQuery(true);
		$deleteQuery->delete('#__organizer_user_lessons');

		foreach ($participants as $participant)
		{
			$unitID = $participant['lessonID'];

			$instanceQuery->clear('where')->where("i.unitID = $unitID");
			$this->_db->setQuery($instanceQuery);

			if ($instanceIDs = Helpers\OrganizerHelper::executeQuery('loadColumn', []))
			{
				$cpData            = ['courseID' => $courseID, 'participantID' => $participant['userID']];
				$courseParticipant = new Tables\CourseParticipants();
				if (!$courseParticipant->load($cpData))
				{
					$cpData['participantDate'] = $participant['user_date'];
					$cpData['status']          = $participant['status'];
					$cpData['statusDate']      = $participant['status_date'];
					$cpData['attended']        = true;
					$cpData['paid']            = true;
					$courseParticipant->save($cpData);
				}

				foreach ($instanceIDs as $instanceID)
				{
					$ipData              = ['instanceID' => $instanceID, 'participantID' => $participant['userID']];
					$instanceParticipant = new Tables\InstanceParticipants();
					if (!$instanceParticipant->load($ipData))
					{
						$instanceParticipant->save($ipData);
					}
				}
			}
		}

		return true;
	}

	/**
	 * Attempts to save the resource.
	 *
	 * @param   array  $data  the data from the form
	 *
	 * @return int|bool int id of the resource on success, otherwise boolean false
	 * @throws Exception => unauthorized access
	 */
	public function save($data = [])
	{
		if (!$this->allow())
		{
			throw new Exception(Helpers\Languages::_('COM_ORGANIZER_403'), 403);
		}

		$data  = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;
		$table = $this->getTable();

		if (empty($data['id']))
		{
			return $table->save($data) ? $table->id : false;
		}

		if (!$table->load($data['id']))
		{
			return false;
		}

		foreach ($data as $column => $value)
		{
			$table->$column = $value;
		}

		return $table->store() ? $table->id : false;
	}
}
