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
use Organizer\Validators;
use Organizer\Tables;

/**
 * Class which manages stored schedule data.
 * Note on access checks: since schedule access rights are set by organization, checking the access rights for one
 * schedule is sufficient for any other schedule modified in the same context.
 */
class Schedule extends BaseModel
{
	private $modified;

	/**
	 * Deletes the selected schedules
	 *
	 * @return boolean true on successful deletion of all selected schedules, otherwise false
	 */
	public function delete()
	{
		if (!Helpers\Can::scheduleTheseOrganizations())
		{
			Helpers\OrganizerHelper::error(403);
		}

		$scheduleIDs = Helpers\Input::getSelectedIDs();
		foreach ($scheduleIDs as $scheduleID)
		{
			if (!$this->deleteSingle($scheduleID))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Removed duplicate entries (creationDate, creationTime, organizationID, termID) from the schedules table. No
	 * authorization checks, because abuse would not result in actual data loss.
	 *
	 * @return void
	 */
	private function deleteDuplicates()
	{
		$conditions = 's1.creationDate = s2.creationDate AND s1.creationTime = s2.creationTime
						AND s1.organizationID = s2.organizationID AND s1.termID = s2.termID';

		$query = $this->_db->getQuery(true);
		$query->select('s1.id')
			->from('#__organizer_schedules AS s1')
			->innerJoin("#__organizer_schedules AS s2 ON $conditions")
			->where('s1.id < s2.id');
		$this->_db->setQuery($query);

		if (!$duplicateIDs = Helpers\OrganizerHelper::executeQuery('loadColumn', []))
		{
			return;
		}

		foreach ($duplicateIDs as $duplicateID)
		{
			$this->deleteSingle($duplicateID);
		}
	}

	/**
	 * Deletes a single internal schedule entry and any corresponding external schedule entry that may exist.
	 *
	 * @param $scheduleID
	 *
	 * @return bool
	 */
	private function deleteSingle($scheduleID)
	{
		if (!Helpers\Can::schedule('schedule', $scheduleID))
		{
			Helpers\OrganizerHelper::error(403);
		}

		$schedule = new Tables\Schedules();

		if ($schedule->load($scheduleID) and !$schedule->delete())
		{
			return false;
		}

		$query = $this->_db->getQuery(true);
		$query->delete('#__thm_organizer_schedules')->where("id = $scheduleID");
		$this->_db->setQuery($query);
		Helpers\OrganizerHelper::executeQuery('execute');

		return true;
	}

	/**
	 * Retrieves the ids of the resources associated with the given fk values.
	 *
	 * @param   string  $suffix    the specific portion of the table name
	 * @param   string  $fkColumn  the name of the fk column
	 * @param   string  $fkValues  the fk column values
	 *
	 * @return mixed|null
	 */
	private function getAssociatedIDs($suffix, $fkColumn, $fkValues)
	{
		$fkValues = implode(', ', $fkValues);
		$query    = $this->_db->getQuery(true);
		$query->select('id')->from("#__organizer_$suffix")->where("$fkColumn IN ($fkValues)");
		$this->_db->setQuery($query);

		return Helpers\OrganizerHelper::executeQuery('loadColumn', []);
	}

	/**
	 * Returns the schedule IDs relevant for the context ordered earliest to latest.
	 *
	 * @param   int  $organizationID  the id of the organization context
	 * @param   int  $termID          the id of the term context
	 *
	 * @return array the schedule ids
	 */
	private function getContextIDs($organizationID, $termID)
	{
		$query = $this->_db->getQuery(true);
		$query->select('id')
			->from('#__organizer_schedules')
			->where("organizationID = $organizationID")
			->where("termID = $termID")
			->order('creationDate, creationTime');
		$this->_db->setQuery($query);

		return Helpers\OrganizerHelper::executeQuery('loadColumn', []);
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
	 * Notifies the points of contact for affected organizations of changes made to the schedule.
	 *
	 * @return bool
	 */
	/*public function notify()
	{
		if (!$selectedIDs = Helpers\Input::getSelectedIDs())
		{
			return false;
		}

		$referenceID = array_shift($selectedIDs);

		if (!Helpers\Can::schedule('schedule', $referenceID))
		{
			Helpers\OrganizerHelper::error(403);
		}

		$reference = new Tables\Schedules();
		if (!$reference->load($referenceID))
		{
			return false;
		}

		$current = new Tables\Schedules();
		if (empty($selectedIDs))
		{
			if (!$contextIDs = $this->getContextIDs($reference->organizationID, $reference->termID))
			{
				return false;
			}

			$currentID = array_pop($contextIDs);
		}
		else
		{
			$currentID = array_shift($selectedIDs);
		}

		// Request manipulation
		if ($currentID == $referenceID
			or !$current->load($currentID)
			or $current->organizationID !== $reference->organizationID
			or $current->termID !== $reference->termID)
		{
			return false;
		}

		$reference = json_decode($reference->schedule, true);
		$current   = json_decode($current->schedule, true);

		return true;
	}*/

	/**
	 * Rebuilds the history of a organization / term context.
	 *
	 * @return bool
	 */
	public function rebuild()
	{
		if (!$organizationID = Helpers\Input::getFilterID('organization') or !$termID = Helpers\Input::getFilterID('term'))
		{
			return false;
		}

		if (!Helpers\Can::schedule('organization', $organizationID))
		{
			Helpers\OrganizerHelper::error(403);
		}

		$this->deleteDuplicates();

		if (!$scheduleIDs = $this->getContextIDs($organizationID, $termID))
		{
			return true;
		}

		$this->resetContext($organizationID, $termID, $scheduleIDs[0]);

		foreach ($scheduleIDs as $scheduleID)
		{
			$this->setCurrent($scheduleID);
		}

		return true;
	}

	/**
	 * Rebuilds the history of a organization / term context.
	 *
	 * @return bool
	 */
	public function reference()
	{
		if (!$referenceID = Helpers\Input::getSelectedID())
		{
			return false;
		}

		if (!Helpers\Can::schedule('schedule', $referenceID))
		{
			Helpers\OrganizerHelper::error(403);
		}

		$reference = new Tables\Schedules();
		if (!$reference->load($referenceID))
		{
			return false;
		}

		if (!$scheduleIDs = $this->getContextIDs($reference->organizationID, $reference->termID))
		{
			return true;
		}

		$currentID = array_pop($scheduleIDs);
		$current   = new Tables\Schedules();
		if (!$current->load($currentID))
		{
			return false;
		}

		// The entries up to and including the reference id are ignored. The entries after are deleted.
		$delete = false;
		foreach ($scheduleIDs as $scheduleID)
		{
			if ($delete)
			{
				$this->deleteSingle($scheduleID);
			}

			if ($scheduleID == $referenceID)
			{
				$delete = true;
			}
		}

		$this->setCurrent($referenceID);
		$this->setCurrent($currentID);

		return true;
	}

	/**
	 * Resets all associated resources to a removed status with a date of one week before the timestamp of the first
	 * schedule.
	 *
	 * @param   int  $organizationID  the id of the organization context
	 * @param   int  $termID          the id of the term context
	 * @param   int  $baseID          the id if the schedule to be used to generate the reset timestamp
	 *
	 * @return void
	 */
	private function resetContext($organizationID, $termID, $baseID)
	{
		$firstSchedule = new Tables\Schedules();
		$firstSchedule->load($baseID);
		$timestamp = "$firstSchedule->creationDate $firstSchedule->creationTime";
		unset($firstSchedule);

		$modified   = date('Y-m-d h:i:s', strtotime('-2 Weeks', strtotime($timestamp)));
		$conditions = ["delta = 'removed'", "modified = '$modified'"];

		$query = $this->_db->getQuery(true);
		$query->select('id')
			->from('#__organizer_units')
			->where("organizationID = $organizationID")
			->where("termID = $termID");
		$this->_db->setQuery($query);

		if (!$unitIDs = Helpers\OrganizerHelper::executeQuery('loadColumn', []))
		{
			return;
		}
		$this->updateBatch('units', $unitIDs, $conditions);

		if (!$instanceIDs = $this->getAssociatedIDs('instances', 'unitID', $unitIDs))
		{
			return;
		}
		$this->updateBatch('instances', $instanceIDs, $conditions);

		if (!$assocIDs = $this->getAssociatedIDs('instance_persons', 'instanceID', $instanceIDs))
		{
			return;
		}
		$this->updateBatch('instance_persons', $assocIDs, $conditions);

		if (!$igIDs = $this->getAssociatedIDs('instance_groups', 'assocID', $assocIDs))
		{
			return;
		}
		$this->updateBatch('instance_groups', $igIDs, $conditions);

		if (!$irIDs = $this->getAssociatedIDs('instance_rooms', 'assocID', $assocIDs))
		{
			return;
		}
		$this->updateBatch('instance_rooms', $irIDs, $conditions);
	}

	/**
	 * Attempts to resolve events to subjects via associations and curriculum mapping.
	 *
	 * @param   int  $organizationID  the id of the organization with which the events are associated
	 *
	 * @return void
	 */
	private function resolveEventSubjects(int $organizationID)
	{
		$query = $this->_db->getQuery(true);
		$query->select('id, subjectNo')
			->from('#__organizer_events')
			->where("organizationID = $organizationID")
			->where("subjectNo != ''");
		$this->_db->setQuery($query);

		if (!$events = Helpers\OrganizerHelper::executeQuery('loadAssocList', []))
		{
			return;
		}

		foreach ($events as $event)
		{
			$query = $this->_db->getQuery(true);
			$query->select('DISTINCT lft, rgt')
				->from('#__organizer_curricula AS c')
				->innerJoin('#__organizer_programs AS prg ON prg.id = c.programID')
				->innerJoin('#__organizer_categories AS cat ON cat.id = prg.categoryID')
				->innerJoin('#__organizer_groups AS gr ON gr.categoryID = cat.id')
				->innerJoin('#__organizer_instance_groups AS ig ON ig.groupID = gr.id')
				->innerJoin('#__organizer_instance_persons AS ip ON ip.id = ig.assocID')
				->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
				->where("i.eventID = {$event['id']}");
			$this->_db->setQuery($query);

			if (!$boundaries = Helpers\OrganizerHelper::executeQuery('loadAssoc', []))
			{
				continue;
			}

			$subjectQuery = $this->_db->getQuery(true);
			$subjectQuery->select('subjectID')
				->from('#__organizer_curricula AS m')
				->innerJoin('#__organizer_subjects as s on m.subjectID = s.id')
				->where("m.lft > '{$boundaries['lft']}'")
				->where("m.rgt < '{$boundaries['rgt']}'")
				->where("s.code = '{$event['subjectNo']}'");
			$this->_db->setQuery($subjectQuery);

			if (!$subjectID = Helpers\OrganizerHelper::executeQuery('loadResult'))
			{
				continue;
			}

			$data         = ['subjectID' => $subjectID, 'eventID' => $event['id']];
			$subjectEvent = new Tables\SubjectEvents();

			if ($subjectEvent->load($data))
			{
				continue;
			}

			$subjectEvent->save($data);
		}
	}

	/**
	 * Sets the schedule with the given id as the current one in regard to the status of planned relationships and
	 * resources in its organization / term context.
	 *
	 * @param   int  $scheduleID  the id of the schedule to set as current
	 *
	 * @return void
	 */
	public function setCurrent($scheduleID)
	{
		$schedule = new Tables\Schedules();

		if (!$schedule->load($scheduleID))
		{
			return;
		}

		$iGroup         = new Tables\InstanceGroups();
		$instance       = new Tables\Instances();
		$instances      = json_decode($schedule->schedule, true);
		$iPerson        = new Tables\InstancePersons();
		$iRoom          = new Tables\InstanceRooms();
		$this->modified = "$schedule->creationDate $schedule->creationTime";
		$unit           = new Tables\Units();
		$unitIDs        = [];

		foreach ($instances as $instanceID => $persons)
		{
			if (!$instance->load($instanceID))
			{
				continue;
			}

			if ($instance->modified !== $this->modified)
			{
				$instance->delta    = $instance->delta === 'removed' ? 'new' : '';
				$instance->modified = $this->modified;
				$instance->store();
			}

			$unitIDs[$instance->unitID] = $instance->unitID;

			foreach ($persons as $personID => $resources)
			{
				if (!$iPerson->load(['instanceID' => $instanceID, 'personID' => $personID]))
				{
					continue;
				}

				if ($iPerson->modified !== $this->modified)
				{
					$iPerson->delta    = $iPerson->delta === 'removed' ? 'new' : '';
					$iPerson->modified = $this->modified;
					$iPerson->store();
				}

				foreach ($resources['groups'] as $groupID)
				{
					if (!$iGroup->load(['assocID' => $iPerson->id, 'groupID' => $groupID])
						or $iGroup->modified === $this->modified)
					{
						continue;
					}

					$iGroup->delta    = $iGroup->delta === 'removed' ? 'new' : '';
					$iGroup->modified = $this->modified;
					$iGroup->store();
				}

				$this->setRemoved('instance_groups', ['assocID' => $iPerson->id], 'groupID', $resources['groups']);

				foreach ($resources['rooms'] as $roomID)
				{
					if (!$iRoom->load(['assocID' => $iPerson->id, 'roomID' => $roomID])
						or $iRoom->modified === $this->modified)
					{
						continue;
					}

					$iRoom->delta    = $iRoom->delta === 'removed' ? 'new' : '';
					$iRoom->modified = $this->modified;
					$iRoom->store();
				}

				$this->setRemoved('instance_rooms', ['assocID' => $iPerson->id], 'roomID', $resources['rooms']);
			}

			$this->setRemoved('instance_persons', ['instanceID' => $instanceID], 'personID', array_keys($persons));

		}

		$fkPairs = ['organizationID' => $schedule->organizationID, 'termID' => $schedule->termID];
		$this->setRemoved('units', $fkPairs, 'id', $unitIDs);
		$instanceIDs = array_keys($instances);

		foreach ($unitIDs as $unitID)
		{
			if (!$unit->load($unitID) or $unit->modified === $this->modified)
			{
				continue;
			}

			if ($unit->modified !== $this->modified)
			{
				$unit->delta    = $unit->delta === 'removed' ? 'new' : '';
				$unit->modified = $this->modified;
				$unit->store();
			}

			$this->setRemoved('instances', ['unitID' => $unitID], 'id', $instanceIDs);
		}
	}

	/**
	 * Sets the resources and relations to removed which are not associated with a given schedule relation / resource.
	 *
	 * @param   string  $suffix          the specific part of the organizer table name
	 * @param   array   $fkPairs         the fk key => value pairs defining the context for relevance
	 * @param   string  $resourceColumn  the identifying resource column
	 * @param   array   $resourceIDs     the current relation / resource ids
	 *
	 * @return void
	 */
	private function setRemoved($suffix, $fkPairs, $resourceColumn, $resourceIDs)
	{
		$query = $this->_db->getQuery(true);
		$query->update("#__organizer_$suffix")
			->set("delta = 'removed'")
			->set("modified = '$this->modified'")
			->where("delta != 'removed'");

		foreach ($fkPairs as $column => $value)
		{
			$query->where("$column = $value");
		}

		if (count($resourceIDs))
		{
			$resourceIDs = implode(', ', $resourceIDs);
			$query->where("$resourceColumn NOT IN ($resourceIDs)");
		}

		$this->_db->setQuery($query);

		Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Updates entries in the given entry ids in the given table with the given conditions.
	 *
	 * @param   string  $suffix      the specific portion of the table name
	 * @param   array   $entryIDs    the ids of the entries to update
	 * @param   array   $conditions  the set conditions
	 *
	 * @return void
	 */
	private function updateBatch($suffix, $entryIDs, $conditions)
	{
		$entryIDs = implode(', ', $entryIDs);
		$query    = $this->_db->getQuery(true);
		$query->update("#__organizer_$suffix")->set($conditions)->where("id IN ($entryIDs)");
		$this->_db->setQuery($query);

		Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Saves a schedule in the database for later use
	 *
	 * @return  boolean true on success, otherwise false
	 */
	public function upload()
	{
		if (!$organizationID = Helpers\Input::getInt('organizationID'))
		{
			return false;
		}

		if (!Helpers\Can::schedule('organization', $organizationID))
		{
			Helpers\OrganizerHelper::error(403);
		}

		$validator = new Validators\Schedule();

		if (!$validator->validate())
		{
			return false;
		}

		$userID = Helpers\Users::getID();

		$validator->schedule->lessons = $validator->units;

		$data = [
			'creationDate' => $validator->creationDate,
			'creationTime' => $validator->creationTime,
			'departmentID' => $organizationID,
			'schedule'     => json_encode($validator->schedule),
			'termID'       => $validator->termID,
			'userID'       => $userID
		];

		$schedule = new Tables\OldSchedules();
		if (!$schedule->save($data))
		{
			return false;
		}

		$json = new ScheduleJSON();
		$json->setReference($schedule->id);
		unset($validator->schedule);

		$data = [
			'creationDate'   => $validator->creationDate,
			'creationTime'   => $validator->creationTime,
			'organizationID' => $organizationID,
			'schedule'       => json_encode($validator->instances),
			'termID'         => $validator->termID,
			'userID'         => $userID
		];

		$schedule = new Tables\Schedules();
		if (!$schedule->save($data))
		{
			return false;
		}

		$this->setCurrent($schedule->id);
		$this->resolveEventSubjects($organizationID);

		return true;
	}
}