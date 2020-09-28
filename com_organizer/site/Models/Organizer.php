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

use Joomla\CMS\Table\Table;
use Joomla\CMS\Toolbar\Toolbar;
use Organizer\Helpers\OrganizerHelper;

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
	public function removeAssets()
	{
		$query = $this->_db->getQuery(true);
		$query->select('id')->from('#__assets')->where("name LIKE 'com_thm_organizer.department.%'");
		$this->_db->setQuery($query);

		$assetIDs = OrganizerHelper::executeQuery('loadColumn', []);

		foreach ($assetIDs as $assetID)
		{
			$asset = Table::getInstance('asset');
			$asset->delete($assetID);
		}

		return true;
	}

	/**
	 * Migrates associations with a given participant id.
	 *
	 * @param   int  $participantID  the id of the participant whose date should be migrated
	 *
	 * @return bool true on success, otherwise false.
	 */
	/*private function migrateParticipantAssociations($participantID)
	{
		$userLessonsQuery = $this->_db->getQuery(true);
		$userLessonsQuery->select('*')->from('#__organizer_user_lessons')->where("userID = $participantID");
		$this->_db->setQuery($userLessonsQuery);

		if (!$userLessons = OrganizerHelper::executeQuery('loadAssocList', []))
		{
			return true;
		}

		$blocksConditions = 'b.date = cal.schedule_date AND b.startTime = cal.startTime AND b.endTime = cal.endTime';
		$dataQuery        = $this->_db->getQuery(true);
		$dataQuery->select('cor.id AS courseID, i.id AS instanceID')
			->from('#__organizer_calendar_configuration_map AS ccm')
			->innerJoin('#__organizer_calendar AS cal ON cal.id = ccm.calendarID')
			->innerJoin("#__organizer_blocks AS b ON $blocksConditions")
			->innerJoin('#__organizer_units AS u ON u.id = cal.lessonID')
			->innerJoin('#__organizer_lesson_configurations AS lc ON lc.id = ccm.configurationID')
			->innerJoin('#__organizer_lesson_subjects AS ls ON ls.id = lc.lessonID AND ls.lessonID = u.id')
			->innerJoin('#__organizer_events AS e ON e.id = ls.subjectID')
			->innerJoin('#__organizer_instances AS i ON i.blockID = b.id AND i.eventID = e.id AND i.unitID = u.id')
			->leftJoin('#__organizer_courses AS cor ON cor.id = u.courseID');

		$deleteQuery = $this->_db->getQuery(true);
		$deleteQuery->delete('#__organizer_user_lessons');

		foreach ($userLessons as $userLesson)
		{
			$unitID = $userLesson['lessonID'];
			$ccmIDs = str_replace(['[', '"', ']'], '', $userLesson['configuration']);

			$dataQuery->clear('where');
			$dataQuery->where("u.id = $unitID");
			$dataQuery->where("ccm.id IN ($ccmIDs)");
			$this->_db->setQuery($dataQuery);

			if ($results = OrganizerHelper::executeQuery('loadAssocList', []))
			{

				// The courseID is the same for every result.
				if ($results[0]['courseID'])
				{
					$this->saveCourseParticipant($results[0]['courseID'], $participantID, $userLesson);
				}

				foreach ($results as $result)
				{
					$this->saveInstanceParticipant($result['instanceID'], $participantID, $userLesson);
				}
			}


			$deleteQuery->clear('where');
			$deleteQuery->where("id = {$userLesson['id']}");
			$this->_db->setQuery($deleteQuery);
			OrganizerHelper::executeQuery('execute');
		}
	}*/

	/**
	 * Migrates the participants of a given course from the old structure to the new structure.
	 *
	 * @return bool
	 */
	/*public function migrateParticipants()
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
	}*/

	/**
	 * Migrates user lessons.
	 *
	 * @return bool true on success, otherwise false.
	 */
	/*public function migrateUserLessons()
	{
		$selectQuery = $this->_db->getQuery(true);
		$selectQuery->select('DISTINCT userID')->from('#__organizer_user_lessons');
		$this->_db->setQuery($selectQuery);

		$participantIDs = OrganizerHelper::executeQuery('loadColumn', []);
		foreach ($participantIDs as $participantID)
		{
			$this->migrateParticipantAssociations($participantID);
		}

		return true;
	}*/

	/**
	 * Creates or modifies a course participant table entry.
	 *
	 * @param   int    $courseID       the id of the course
	 * @param   int    $participantID  the id of the participant
	 * @param   array  $userLesson     the data from the previously save entry
	 *
	 * @return void
	 */
	/*private function saveCourseParticipant($courseID, $participantID, $userLesson)
	{
		$cParticipants = new Tables\CourseParticipants;
		$cParticipant  = ['courseID' => $courseID, 'participantID' => $participantID];
		$cParticipants->load($cParticipant);
		if (empty($cParticipants->id))
		{
			$cParticipant['participantDate'] = $userLesson['user_date'];
			$cParticipant['status']          = $userLesson['status'];
			$cParticipant['statusDate']      = $userLesson['status_date'];
			$cParticipant['attended']        = true;
			$cParticipant['paid']            = true;
			$cParticipants->save($cParticipant);
		}
		else
		{
			$altered = false;

			if ($cParticipants->participantDate < $userLesson['user_date'])
			{
				$cParticipants->participantDate = $userLesson['user_date'];

				$altered = true;
			}

			if ($cParticipants->statusDate < $userLesson['status_date'])
			{
				$cParticipants->statusDate = $userLesson['status_date'];
				$cParticipants->status     = $userLesson['status'];

				$altered = true;
			}

			if ($altered)
			{
				$cParticipants->store();
			}
		}
	}*/

	/**
	 * Creates or modifies an instance participant table entry.
	 *
	 * @param   int    $instanceID     the id of the instance
	 * @param   int    $participantID  the id of the participant
	 * @param   array  $userLesson     the data from the previously save entry
	 *
	 * @return void
	 */
	/*private function saveInstanceParticipant($instanceID, $participantID, $userLesson)
	{
		$iParticipants = new Tables\InstanceParticipants;
		$iParticipant  = ['instanceID' => $instanceID, 'participantID' => $participantID];
		$iParticipants->load($iParticipant);
		if (empty($iParticipants->id))
		{
			$iParticipants->save($iParticipant);
		}
	}*/

	/**
	 * Checks whether user lessons or lesson configurations exist which yet need to be migrated. Provides buttons to
	 * trigger migration as necessary. Drops the corresponding tables if all data has been migrated.
	 *
	 * @param   Toolbar  $toolbar  the toolbar to add the button to as necessary.
	 *
	 * @return void
	 */
	/*public function showConfigurationMigrationButtons($toolbar)
	{
		$prefix = $this->_db->getPrefix();
		$this->_db->setQuery('SHOW TABLES');
		$tables = OrganizerHelper::executeQuery('loadColumn', []);

		$userLessonsTable = $prefix . 'organizer_user_lessons';

		if (in_array($userLessonsTable, $tables))
		{
			$this->supplementParticipants();

			$lessonCountQuery = $this->_db->getQuery(true);
			$lessonCountQuery->select('COUNT(*)')->from('#__organizer_user_lessons');
			$this->_db->setQuery($lessonCountQuery);

			if (OrganizerHelper::executeQuery('loadResult', 0))
			{
				$toolbar->appendButton(
					'Standard',
					'users',
					'Migrate User Lessons',
					'organizer.migrateUserLessons',
					false
				);

				return;
			}
			else
			{
				$this->_db->setQuery('DROP TABLE `#__organizer_user_lessons`');
				OrganizerHelper::executeQuery('execute');
			}
		}

		$mapTable = $prefix . 'organizer_calendar_configuration_map';

		if (in_array($mapTable, $tables))
		{
			$configCountQuery = $this->_db->getQuery(true);
			$configCountQuery->select('COUNT(*)')->from('#__organizer_calendar_configuration_map');
			$this->_db->setQuery($configCountQuery);

			if (OrganizerHelper::executeQuery('loadResult', 0))
			{
				$toolbar->appendButton(
					'Standard',
					'next',
					'Migrate Configurations',
					'organizer.migrateConfigurations',
					false
				);

				return;
			}
			else
			{
				$this->_db->setQuery('DROP TABLE `#__organizer_calendar_configuration_map`');
				OrganizerHelper::executeQuery('execute');

				$this->_db->setQuery('DROP TABLE `#__organizer_calendar`');
				OrganizerHelper::executeQuery('execute');

				$this->_db->setQuery('DROP TABLE `#__organizer_lesson_configurations`');
				OrganizerHelper::executeQuery('execute');

				$this->_db->setQuery('DROP TABLE `#__organizer_lesson_subjects`');
				OrganizerHelper::executeQuery('execute');
			}
		}
	}*/

	/**
	 * Adds users who have created schedules to the participants table.
	 *
	 * @return void
	 */
	/*private function supplementParticipants()
	{
		$participantQuery = $this->_db->getQuery(true);
		$participantQuery->select('DISTINCT id')->from('#__organizer_participants');
		$selectQuery = $this->_db->getQuery(true);
		$selectQuery->select('DISTINCT userID')
			->from('#__organizer_user_lessons')
			->where("userID NOT IN ($participantQuery)");
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
	}*/
}
