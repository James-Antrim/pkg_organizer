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
use Joomla\CMS\Factory;
use Organizer\Helpers;
use Organizer\Helpers\OrganizerHelper;
use Organizer\Helpers\Validators;
use Organizer\Tables;

/**
 * Class which manages stored schedule data.
 * Note on access checks: since schedule access rights are set by organization, checking the access rights for one
 * schedule is sufficient for any other schedule modified in the same context.
 */
class Schedule extends BaseModel
{
	private $organizationID;
	private $instanceIDs;
	private $instances;
	private $termID;
	private $unitIDs;

	/**
	 * Sets the selected schedule to inactive.
	 *
	 * @param   int  $scheduleID      the id of the schedule to deactivate
	 * @param   int  $organizationID  the id of the organization context for the schedule to deactivate
	 * @param   int  $termID          the id of the term context for the schedule to deactivate
	 *
	 * @return bool
	 */
	private function authorizedDeactivate($scheduleID = 0, $organizationID = 0, $termID = 0)
	{
		$conditions = empty($scheduleID) ?
			['active' => 1, 'organizationID' => $organizationID, 'termID' => $termID] : $scheduleID;
		$table      = new Tables\Schedules;

		if (!$table->load($conditions))
		{
			return false;
		}

		$table->set('active', 0);

		return $table->store();
	}

	/**
	 * Moves schedules from the old table to the new table.
	 *
	 * @param   string  $date  the reference modification date
	 *
	 * @return bool true on success, otherwise false
	 */
	private function blocks($date)
	{
		$query = "INSERT IGNORE INTO #__organizer_blocks (`date`, `dow`, `startTime`, `endTime`)
				SELECT DISTINCT schedule_date, WEEKDAY(schedule_date) + 1, startTime, endTime
				FROM v7ocf_thm_organizer_calendar
				WHERE modified >= '$date';";
		$this->_db->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Deletes the selected schedules
	 *
	 * @return boolean true on successful deletion of all selected schedules, otherwise false
	 * @throws Exception Unauthorized Access
	 */
	public function delete()
	{
		if (!Helpers\Can::scheduleTheseOrganizations())
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
		}

		$scheduleIDs = Helpers\Input::getSelectedIDs();
		foreach ($scheduleIDs as $scheduleID)
		{
			if (!Helpers\Can::schedule('schedule', $scheduleID))
			{
				throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
			}

			$schedule = new Tables\Schedules;

			if ($schedule->load($scheduleID) and !$schedule->delete())
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Schedules A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Schedules;
	}

	/**
	 * Retrieves the unit ids associated with the given instanceIDs
	 *
	 * @param   array  $instanceIDs  the ids of the currently active instances
	 *
	 * @return array the unitIDs associated with the instances
	 */
	private function getUnitIDs($instanceIDs)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('DISTINCT unitID')
			->from('#__organizer_instances')
			->where('id IN (' . implode(',', $instanceIDs) . ')');
		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadColumn', []);
	}

	/**
	 * Moves schedules from the old table to the new table.
	 *
	 * @param   string  $date  the reference modification date
	 *
	 * @return bool true on success, otherwise false
	 */
	private function instanceGroups($date)
	{
		$query = "INSERT IGNORE INTO #__organizer_instance_groups (assocID, groupID, delta, modified)
				SELECT DISTINCT ip.id, lp.poolID, lp.delta, lp.modified
				FROM #__thm_organizer_lesson_pools AS lp
				INNER JOIN #__thm_organizer_lesson_subjects AS ls ON ls.id = lp.subjectID
				INNER JOIN #__organizer_instances AS i ON i.eventID = ls.subjectID AND i.unitID = ls.lessonID
				INNER JOIN #__organizer_instance_persons AS ip ON ip.instanceID = i.id
				WHERE lp.modified >= '$date'
				GROUP BY ip.id, lp.poolID;";
		$this->_db->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Moves schedules from the old table to the new table.
	 *
	 * @param   string  $date  the reference modification date
	 *
	 * @return bool true on success, otherwise false
	 */
	private function instancePersons($date)
	{
		$query = "INSERT IGNORE INTO #__organizer_instance_persons (instanceID, personID, delta, modified)
				SELECT DISTINCT i.id, lt.teacherID, lt.delta, lt.modified
				FROM #__thm_organizer_lesson_teachers AS lt
				INNER JOIN #__thm_organizer_lesson_subjects AS ls ON ls.id = lt.subjectID
				INNER JOIN #__organizer_instances AS i ON i.eventID = ls.subjectID AND i.unitID = ls.lessonID
				WHERE lt.modified >= '$date'
				GROUP BY i.id, lt.teacherID;";
		$this->_db->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Moves schedules from the old table to the new table.
	 *
	 * @param   string  $date  the reference modification date
	 *
	 * @return bool true on success, otherwise false
	 */
	private function instanceRooms($date)
	{
		$query = $this->_db->getQuery(true);
		$query->select('DISTINCT ccm.id')
			->from('#__thm_organizer_calendar_configuration_map AS ccm')
			->innerJoin('#__thm_organizer_lesson_configurations AS lc ON lc.id = ccm.configurationID')
			->where("lc.modified >= '$date'");

		$this->_db->setQuery($query);

		foreach (OrganizerHelper::executeQuery('loadColumn', []) as $mapID)
		{
			$query = "SELECT lc.configuration, i.id AS instanceID, lc.modified
			FROM #__thm_organizer_lesson_configurations AS lc
			INNER JOIN #__thm_organizer_calendar_configuration_map AS ccm ON ccm.configurationID = lc.id
			INNER JOIN #__thm_organizer_calendar AS cal ON cal.id = ccm.calendarID
			INNER JOIN #__organizer_blocks AS b ON b.date = cal.schedule_date AND b.startTime = cal.startTime AND b.endTime = cal.endTime
			INNER JOIN #__organizer_units AS u ON u.id = cal.lessonID
			INNER JOIN #__thm_organizer_lesson_subjects AS ls ON ls.id = lc.lessonID AND ls.lessonID = u.id
			INNER JOIN #__organizer_events AS e ON e.id = ls.subjectID
			INNER JOIN #__organizer_instances AS i ON i.blockID = b.id AND i.eventID = e.id AND i.unitID = u.id
			WHERE ccm.id = $mapID;";

			$this->_db->setQuery($query);

			if (!$assoc = OrganizerHelper::executeQuery('loadAssoc', []))
			{
				continue;
			}

			$configuration = json_decode($assoc['configuration'], true);

			foreach ($configuration['teachers'] as $personID => $personDelta)
			{

				$instancePersons = new Tables\InstancePersons;
				$instancePersons->load(['instanceID' => $assoc['instanceID'], 'personID' => $personID]);

				if ($assocID = $instancePersons->id)
				{
					foreach ($configuration['rooms'] as $roomID => $roomDelta)
					{
						$query = "INSERT IGNORE INTO #__organizer_instance_rooms (assocID, roomID, delta, modified)
								VALUES ($assocID, $roomID, '$roomDelta', '{$assoc['modified']}');";
						$this->_db->setQuery($query);

						if (!OrganizerHelper::executeQuery('execute'))
						{
							OrganizerHelper::message($query, 'error');

							return false;
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * Moves schedules from the old table to the new table.
	 *
	 * @param   string  $date  the reference modification date
	 *
	 * @return bool true on success, otherwise false
	 */
	private function instances($date)
	{
		$query = "INSERT IGNORE INTO #__organizer_instances (eventID, blockID, unitID, methodID, delta, modified)
				SELECT ls.subjectID, b.id, u.id, u.methodID, c.delta, c.modified
				FROM #__thm_organizer_lesson_subjects AS ls
				INNER JOIN #__organizer_units AS u ON u.id = ls.lessonID
				INNER JOIN #__thm_organizer_calendar AS c ON c.lessonID = ls.lessonID
				INNER JOIN #__organizer_blocks AS b ON b.date = c.schedule_date AND b.startTime = c.startTime AND b.endTime = c.endTime
				WHERE c.modified >= '$date'
				GROUP BY ls.subjectID, b.id, u.id;";
		$this->_db->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Migrates changes made to planning in the last week
	 *
	 * @return bool true on success, otherwise false
	 */
	public function migrateResources()
	{
		$aWeekAgo = date('Y-m-d h:i:00', strtotime('-1 Week'));

		if (!$this->blocks($aWeekAgo))
		{
			OrganizerHelper::message('Blocks failed.', 'error');

			return false;
		}

		if (!$this->instances($aWeekAgo))
		{
			OrganizerHelper::message('Instances failed.', 'error');

			return false;
		}

		if (!$this->instancePersons($aWeekAgo))
		{
			OrganizerHelper::message('Instance persons failed.', 'error');

			return false;
		}

		if (!$this->instanceGroups($aWeekAgo))
		{
			OrganizerHelper::message('Instance groups failed.', 'error');

			return false;
		}

		if (!$this->instanceRooms($aWeekAgo))
		{
			OrganizerHelper::message('Instance rooms failed.', 'error');

			return false;
		}

		return true;
	}

	/**
	 * Restructures the schedules to the new structure.
	 *
	 * @return bool true on success, otherwise false
	 */
	public function migrateSchedules()
	{
		$query = $this->_db->getQuery(true);
		$query->select('id')
			->from('#__organizer_schedules')
			->where("schedule LIKE '%lessons%'")
			->order('creationDate')
			->setLimit(20);
		$this->_db->setQuery($query);

		if (!$selection = OrganizerHelper::executeQuery('loadColumn', []))
		{
			return true;
		}

		$bTable = new Tables\Blocks();
		$eTable = new Tables\Events();
		$iTable = new Tables\Instances();
		$pTable = new Tables\Persons();
		$sTable = new Tables\Schedules();
		$tTable = new Tables\Terms();
		$uTable = new Tables\Units();

		foreach ($selection as $scheduleID)
		{
			if (!$sTable->load($scheduleID))
			{
				continue;
			}

			$schedule = json_decode($sTable->schedule, true);

			unset($schedule['creationDate'],
				$schedule['creationTime'],
				$schedule['departmentID'],
				$schedule['endDate'],
				$schedule['organizationID'],
				$schedule['planningPeriodID'],
				$schedule['referenceID'],
				$schedule['startDate'],
				$schedule['termID']
			);

			if (!$tTable->load($sTable->termID))
			{
				return false;
			}

			$unitKeys = ['organizationID' => $sTable->organizationID, 'termID' => $sTable->termID];

			foreach ($schedule['calendar'] as $date => $times)
			{
				// Remove empty dates and dates beyond the scope of the terms they were created for
				if (empty($times) or $date < $tTable->startDate or $date > $tTable->endDate)
				{
					unset($schedule['calendar'][$date]);
					continue;
				}

				foreach ($times as $blockTimes => $units)
				{
					list($startTime, $endTime) = explode('-', $blockTimes);
					$startTime = preg_replace('/([\d]{2})$/', ':${1}:00', $startTime);
					$endTime   = preg_replace('/([\d]{2})$/', ':${1}:00', $endTime);

					if (!$bTable->load(['date' => $date, 'startTime' => $startTime, 'endTime' => $endTime]))
					{
						unset($schedule['calendar'][$date][$blockTimes]);
						continue;
					}

					foreach ($units as $untisID => $unitData)
					{
						$unitKeys['code'] = $untisID;

						if (!$uTable->load($unitKeys))
						{
							unset($schedule['calendar'][$date][$blockTimes][$untisID]);
							continue;
						}

						$uConfig = $schedule['lessons'][$untisID];

						foreach ($unitData['configurations'] as $key => $index)
						{
							if (empty($schedule['configurations'][$index]))
							{
								continue;
							}

							$iConfig = json_decode($schedule['configurations'][$index], true);
							$rooms   = array_keys($iConfig['rooms']);

							// The event no longer exists or is no longer associated with the unit
							if (!$eTable->load($iConfig['subjectID']))
							{
								unset($schedule['configurations'][$index]);
								continue;
							}

							if (empty($uConfig['subjects'][$eTable->id]))
							{
								unset($schedule['configurations'][$index]);
								continue;
							}

							$eConfig = $uConfig['subjects'][$eTable->id];

							if (!$groups = $eConfig['pools'])
							{
								unset($uConfig['subjects'][$eTable->id]);
								continue;
							}

							$groups = array_keys($groups);

							$instance = ['blockID' => $bTable->id, 'unitID' => $uTable->id, 'eventID' => $eTable->id];

							if (!$iTable->load($instance))
							{
								$instance['methodID'] = empty($uConfig['methodID']) ? null : $uConfig['methodID'];
								$instance['delta']    = $unitData['delta'];

								if (!$iTable->save($instance))
								{
									continue;
								}
							}

							$persons = [];

							foreach (array_keys($iConfig['teachers']) as $personID)
							{
								if (!$pTable->load($personID) or !array_key_exists($personID, $eConfig['teachers']))
								{
									unset($iConfig['teachers'][$personID]);
									continue;
								}

								$persons[$personID] = ['groups' => $groups, 'roleID' => 1, 'rooms' => $rooms];
							}

							if ($persons)
							{
								$schedule[$iTable->id] = $persons;
							}
						}
					}
				}
			}

			unset($schedule['calendar']);
			unset($schedule['configurations']);
			unset($schedule['lessons']);

			$sTable->schedule = json_encode($schedule, JSON_UNESCAPED_UNICODE);

			if (!$sTable->store())
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Moves schedules from the old table to the new table.
	 *
	 * @return bool true on success, otherwise false
	 */
	public function moveSchedules()
	{
		$query = "INSERT INTO #__organizer_schedules (id, organizationID, termID, userID, creationDate, creationTime, schedule, active)
				SELECT id, departmentID, planningPeriodID, userID, creationDate, creationTime, schedule, active
				FROM #__thm_organizer_schedules
				WHERE id NOT IN (SELECT id FROM #__organizer_schedules);";
		$this->_db->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Migrates changes made to planning in the last week
	 *
	 * @return bool true on success, otherwise false
	 */
	public function quickMigration()
	{
		$aWeekAgo = date('Y-m-d h:i:00', strtotime('-10 Minutes'));

		if (!$this->blocks($aWeekAgo))
		{
			OrganizerHelper::message('Blocks failed.', 'error');

			return false;
		}

		if (!$this->instances($aWeekAgo))
		{
			OrganizerHelper::message('Instances failed.', 'error');

			return false;
		}

		if (!$this->instancePersons($aWeekAgo))
		{
			OrganizerHelper::message('Instance persons failed.', 'error');

			return false;
		}

		if (!$this->instanceGroups($aWeekAgo))
		{
			OrganizerHelper::message('Instance groups failed.', 'error');

			return false;
		}

		if (!$this->instanceRooms($aWeekAgo))
		{
			OrganizerHelper::message('Instance rooms failed.', 'error');

			return false;
		}

		return true;
	}

	/**
	 * Sets context variables used to set active or removed schedule items.
	 *
	 * @param   int  $scheduleID  the id of the schedule
	 *
	 * @return void sets object properties
	 */
	private function setDeltaContext($scheduleID)
	{
		$table = new Tables\Schedules;
		if ($table->load($scheduleID))
		{
			$this->organizationID = $table->organizationID;
			$this->instances      = json_decode($table->schedule, true);
			$this->instanceIDs    = array_keys($this->instances);
			$this->termID         = $table->termID;
			$this->unitIDs        = $this->getUnitIDs($this->instanceIDs);

			return true;
		}

		return false;
	}

	/**
	 * Sets resources to removed which are no longer valid in the context of a recently activated/uploaded schedule.
	 *
	 * @return bool
	 */
	private function setRemoved()
	{
		$this->setRemovedInstances();
		$this->setRemovedUnits();

		foreach ($this->instances as $instanceID => $persons)
		{
			$personIDs = array_keys($persons);
			$this->setRemovedResources('instanceID', $instanceID, 'person', $personIDs);

			foreach ($persons as $personID => $associations)
			{
				$instancePersons = new Tables\InstancePersons;
				if (!$instancePersons->load(['instanceID' => $instanceID, 'personID' => $personID]))
				{
					continue;
				}
				$assocID = $instancePersons->id;
				$this->setRemovedResources('assocID', $assocID, 'group', $associations['groups']);
				$roomIDs = empty($associations['rooms']) ? [] : $associations['rooms'];
				$this->setRemovedResources('assocID', $assocID, 'room', $roomIDs);
			}
		}

		return true;
	}

	/**
	 * Sets the status of instances to removed which are not a part of the active schedule.
	 *
	 * @return void
	 */
	private function setRemovedInstances()
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->update('#__organizer_instances')
			->set("delta = 'removed'")
			->where('id NOT IN (' . implode(',', $this->instanceIDs) . ')')
			->where('unitID IN (' . implode(',', $this->unitIDs) . ')')
			->where("delta != 'removed'");
		$dbo->setQuery($query);

		OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Sets the status of removed resources to removed.
	 *
	 * @param   string  $assocColumn   the name of the column referencing the superior association
	 * @param   int     $assocValue    the id value of the superior association
	 * @param   string  $resourceName  the name of the resource to change
	 * @param   array   $resourceIDs   the ids of the currently associated resources
	 *
	 * @return void
	 */
	private static function setRemovedResources($assocColumn, $assocValue, $resourceName, $resourceIDs)
	{
		$column = $resourceName . 'ID';
		$table  = "#__organizer_instance_{$resourceName}s";
		$dbo    = Factory::getDbo();
		$query  = $dbo->getQuery(true);
		$query->update($table)
			->set("delta = 'removed'")
			->where("$assocColumn = $assocValue")
			->where("$column NOT IN (" . implode(',', $resourceIDs) . ")")
			->where("delta != 'removed'");
		$dbo->setQuery($query);

		OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Sets the status of units to removed which are not a part of the active schedule.
	 *
	 * @return void
	 */
	private function setRemovedUnits()
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->update('#__organizer_units')
			->set("delta = 'removed'")
			->where("organizationID = {$this->organizationID}")
			->where("termID = {$this->termID}")
			->where('id NOT IN (' . implode(',', $this->unitIDs) . ')')
			->where("delta != 'removed'");
		$dbo->setQuery($query);

		OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Saves a schedule in the database for later use
	 *
	 * @param   bool  $notify  true if affected participants/persons should be notified
	 *
	 * @return  boolean true on success, otherwise false
	 * @throws Exception Invalid Request / Unauthorized Access
	 */
	public function upload($notify = false)
	{
		$organizationID = Helpers\Input::getInt('organizationID');
		$invalidForm    = (empty($organizationID));

		if ($invalidForm)
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_400'), 400);
		}

		if (!Helpers\Can::schedule('schedule', $organizationID))
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
		}

		$validator = new Validators\Schedules;
		$valid     = $validator->validate();

		if (!$valid)
		{
			return false;
		}

		$this->authorizedDeactivate(0, $organizationID, $validator->termID);

		$data = [
			'active'         => 1,
			'creationDate'   => $validator->creationDate,
			'creationTime'   => $validator->creationTime,
			'organizationID' => $organizationID,
			'schedule'       => json_encode($validator->instances),
			'termID'         => $validator->termID,
			'userID'         => Factory::getUser()->id
		];

		$newTable = new Tables\Schedules;
		if (!$newTable->save($data))
		{
			return false;
		}

		$this->setDeltaContext($newTable->id);

		return $this->setRemoved();
	}
}