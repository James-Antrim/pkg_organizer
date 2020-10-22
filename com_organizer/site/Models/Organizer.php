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

use Joomla\Utilities\ArrayHelper;
use Organizer\Helpers;
use Organizer\Helpers\OrganizerHelper;
use Organizer\Tables;

/**
 * Class which sets permissions for the view.
 */
class Organizer extends BaseModel
{
	/**
	 * Removes deprecated assets associated with the old component
	 *
	 * @return true
	 */
	public function cleanMappings()
	{
		$query = $this->_db->getQuery(true);
		$query->select('MIN(m3.id) AS replacementID, m1.id AS redundantID')
			->from('#__thm_organizer_calendar_configuration_map AS m1')
			->from('#__thm_organizer_calendar_configuration_map AS m2')
			->from('#__thm_organizer_calendar_configuration_map AS m3')
			->where('m1.calendarID = m2.calendarID')
			->where('m1.calendarID = m3.calendarID')
			->where('m1.id > m2.id')
			->order('replacementID')
			->group('m1.calendarID')
			->setLimit(500);
		$this->_db->setQuery($query);

		if (!$redundancies = OrganizerHelper::executeQuery('loadAssocList', []))
		{
			OrganizerHelper::message('No more redundancies.');

			return true;
		}

		$ccm = new Tables\CalendarConfigurationMap();

		foreach ($redundancies as $redundancy)
		{
			$redID = $redundancy['redundantID'];
			$repID = $redundancy['replacementID'];

			$query = $this->_db->getQuery(true);
			$query->select('id, configuration')
				->from('#__thm_organizer_user_lessons')
				->where("configuration REGEXP '$redID'");
			$this->_db->setQuery($query);

			if ($uses = OrganizerHelper::executeQuery('loadAssocList', []))
			{
				foreach ($uses as $use)
				{
					$ccmIDs = ArrayHelper::toInteger(json_decode($use['configuration']));

					if ($key = array_search($redID, $ccmIDs))
					{
						// Both redundant and replacement are in the array
						if (in_array($repID, $ccmIDs))
						{
							unset($ccmIDs[$key]);
						}
						// Redundant is alone => replace
						else
						{
							$ccmIDs[$key] = $repID;
						}

						sort($ccmIDs);

						$userLesson = new Tables\UserLessons();
						$userLesson->load($use['id']);
						$userLesson->configuration = json_encode($ccmIDs);
						$userLesson->store();
					}
				}
			}

			$ccm->delete($redID);
		}

		$subQuery = $this->_db->getQuery(true);
		$subQuery->select('DISTINCT configurationID')
			->from('#__thm_organizer_calendar_configuration_map');
		$query = $this->_db->getQuery(true);
		$query->delete('#__thm_organizer_lesson_configurations')->where("id NOT IN ($subQuery)");
		$this->_db->setQuery($query);

		return OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Migrates the participants of a given course from the old structure to the new structure.
	 *
	 * @return void
	 */
	public function migrateUserLesson($ulID)
	{
		$userLesson = new Tables\UserLessons();

		if (!$userLesson->load($ulID))
		{
			return;
		}

		if (!Helpers\Participants::exists($userLesson->userID))
		{
			$this->supplementParticipants();
		}

		$participantID = $userLesson->userID;
		$ccmIDs        = ArrayHelper::toInteger(json_decode($userLesson->configuration));

		$cConditions   = 'c.schedule_date = b.date AND c.startTime = b.startTime AND c.endTime = b.endTime';
		$cConditions   .= ' AND c.lessonID = i.unitID';
		$ccmConditions = 'ccm.calendarID = c.id AND ccm.configurationID = lc.id';
		$lsConditions  = 'ls.subjectID = i.eventID AND ls.lessonID = i.unitID';

		$query = $this->_db->getQuery(true);
		$query->select('i.id')
			->from('#__organizer_instances AS i')
			->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
			->innerJoin("#__thm_organizer_lesson_subjects AS ls ON $lsConditions")
			->innerJoin('#__thm_organizer_lesson_configurations AS lc ON lc.lessonID = ls.id')
			->innerJoin("#__thm_organizer_calendar AS c ON $cConditions")
			->innerJoin("#__thm_organizer_calendar_configuration_map AS ccm ON $ccmConditions");

		foreach ($ccmIDs as $ccmID)
		{
			$query->clear('where')->where("i.delta != 'removed'")->where("ccm.id = $ccmID");
			$this->_db->setQuery($query);

			// Could not be resolved to an instance or was removed
			if (!$instanceID = OrganizerHelper::executeQuery('loadResult'))
			{
				continue;
			}

			$instanceParticipant = new Tables\InstanceParticipants();
			$relation            = ['instanceID' => $instanceID, 'participantID' => $participantID];
			if ($instanceParticipant->load($relation))
			{
				continue;
			}

			$instanceParticipant->save($relation);
		}

		$userLesson->delete();
	}

	/**
	 * Migrates user lessons.
	 *
	 * @return bool true on success, otherwise false.
	 */
	public function migrateUserLessons()
	{
		$selectQuery = $this->_db->getQuery(true);
		$selectQuery->select('DISTINCT ul.id')
			->from('#__thm_organizer_user_lessons AS ul')
			->innerJoin('#__organizer_units AS u ON u.id = ul.lessonID')
			->where("u.endDate < '2020-09-28'")
			->order('u.endDate')
			->setLimit(10000);
		$this->_db->setQuery($selectQuery);

		if ($results = OrganizerHelper::executeQuery('loadColumn', []))
		{
			foreach ($results as $ulID)
			{
				$this->migrateUserLesson($ulID);
			}
		}
		else
		{
			OrganizerHelper::message('User lessons ending before 2020-09-28 migrated.');
		}

		return true;
	}

	/**
	 * Adds users who have created schedules to the participants table.
	 *
	 * @return bool
	 */
	public function supplementParticipants()
	{
		$participantQuery = $this->_db->getQuery(true);
		$participantQuery->select('DISTINCT id')->from('#__organizer_participants');
		$selectQuery = $this->_db->getQuery(true);
		$selectQuery->select('DISTINCT userID')
			->from('#__thm_organizer_user_lessons')
			->where("userID NOT IN ($participantQuery)")
			->setLimit(1000);
		$this->_db->setQuery($selectQuery);

		if ($missingParticipantIDs = OrganizerHelper::executeQuery('loadColumn', []))
		{
			$insertQuery = $this->_db->getQuery(true);
			$insertQuery->insert('#__organizer_participants');
			$insertQuery->columns('id, forename, surname');
			foreach ($missingParticipantIDs as $participantID)
			{
				$names    = Helpers\Users::resolveUserName($participantID);
				$forename = $insertQuery->quote($names['forename']);
				$surname  = $insertQuery->quote($names['surname']);
				$insertQuery->clear('values');
				$insertQuery->values("$participantID, $forename, $surname");
				$this->_db->setQuery($insertQuery);

				OrganizerHelper::executeQuery('execute');
			}
		}
		else
		{
			OrganizerHelper::message('Participants Supplemented');
		}

		return true;
	}
}
