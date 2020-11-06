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
 * Class provides methods for merging resources. Resource specific tasks are implemented in the extending classes.
 */
abstract class MergeModel extends BaseModel
{
	/**
	 * Merges resource entries and cleans association tables.
	 *
	 * @return bool  true on success, otherwise false
	 * @throws Exception
	 */
	public function merge()
	{
		$this->selected = Helpers\Input::getSelectedIDs();
		sort($this->selected);

		if (!Helpers\Can::administrate())
		{
			Helpers\OrganizerHelper::error(403);
		}

		// Associations have to be updated before entity references are deleted by foreign keys
		if (!$this->updateReferences())
		{
			return false;
		}

		if (!$this->updateSchedules())
		{
			return false;
		}

		$data          = empty($this->data) ? Helpers\Input::getFormItems()->toArray() : $this->data;
		$deprecatedIDs = $this->selected;
		$data['id']    = array_shift($deprecatedIDs);
		$table         = $this->getTable();

		// Remove deprecated resources. This has to be performed first to avoid any potential conflicts over unique keys.
		foreach ($deprecatedIDs as $deprecatedID)
		{
			if (!$table->delete($deprecatedID))
			{
				return false;
			}
		}

		// Save the merged data.
		if (!$table->save($data))
		{
			return false;
		}

		// Any further processing should not iterate over deprecated ids.
		$this->selected = [$data['id']];

		return true;
	}

	/**
	 * Updates an instance person association with persons or rooms.
	 *
	 * @return bool  true on success, otherwise false
	 */
	protected function updateIPReferences()
	{
		$fkColumn    = strtolower($this->name) . 'ID';
		$tableSuffix = strtolower($this->name) . 's';
		$updateIDs   = implode(', ', $this->selected);

		$query = $this->_db->getQuery(true);
		$query->select('*')
			->from("#__organizer_instance_$tableSuffix")
			->where("$fkColumn IN ($updateIDs)")
			->order('assocID, modified');
		$this->_db->setQuery($query);

		if (!$results = Helpers\OrganizerHelper::executeQuery('loadAssocList', []))
		{
			return true;
		}

		$initialSize = count($results);
		$mergeID     = $this->selected[0];
		$nextIndex   = 0;
		$tableClass  = "Organizer\\Tables\\Instance" . ucfirst($this->name) . 's';

		for ($index = 0; $index < $initialSize;)
		{
			$assocTable = new $tableClass();
			$thisAssoc  = $results[$index];
			$nextIndex  = $nextIndex ? $nextIndex : $index + 1;
			$nextAssoc  = empty($results[$nextIndex]) ? [] : $results[$nextIndex];

			// Unique IP association.
			if (empty($nextAssoc) or $thisAssoc['assocID'] !== $nextAssoc['assocID'])
			{
				// A result cannot be loaded. Should not occur.
				if (!$assocTable->load($thisAssoc['id']))
				{
					return false;
				}

				$assocTable->$fkColumn = $mergeID;
				$assocTable->store();

				$index++;
				$nextIndex++;
				continue;
			}

			// Non-unique IP associations.

			// A later assoc has been added or this one was removed => this one is redundant.
			if ($thisAssoc['delta'] === 'removed' or $nextAssoc['delta'] !== 'removed')
			{
				$assocTable->delete($thisAssoc['id']);
				$index++;
				$nextIndex++;
				continue;
			}

			// As long as the later entries associated with the same entry are removed, remove them.
			do
			{
				$assocTable->delete($nextAssoc['id']);
				unset($results[$nextIndex]);

				$nextIndex++;
				$nextAssoc = $results[$nextIndex];

				// Last result associated with the current IP association.
				if ($thisAssoc['assocID'] !== $nextAssoc['assocID'])
				{
					$assocTable->load($thisAssoc['id']);
					$assocTable->$fkColumn = $mergeID;
					$assocTable->store();
					$index = $nextIndex;
					$nextIndex++;
					continue 2;
				}

				// An IP association added later is still current.
				if ($nextAssoc['delta'] !== 'removed')
				{
					$assocTable->delete($thisAssoc['id']);
					$index = $nextIndex;
					$nextIndex++;
					continue 2;
				}
			} while (true);
		}

		return true;
	}

	/**
	 * Updates the resource dependent associations
	 *
	 * @return bool  true on success, otherwise false
	 */
	abstract protected function updateReferences();

	/**
	 * Updates an association where the associated resource itself has a fk reference to the resource being merged.
	 *
	 * @param   string  $table  the unique part of the table name
	 *
	 * @return bool  true on success, otherwise false
	 */
	protected function updateReferencingTable(string $table)
	{
		$fkColumn  = strtolower($this->name) . 'ID';
		$mergeID   = $this->selected[0];
		$updateIDs = implode(', ', $this->selected);

		$query = $this->_db->getQuery(true);
		$query->update("#__organizer_$table");
		$query->set("$fkColumn = $mergeID");
		$query->where("$fkColumn IN ($updateIDs)");
		$this->_db->setQuery($query);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute', false);
	}

	/**
	 * Updates resource associations in a schedule.
	 *
	 * @param   int  $scheduleID  the id of the schedule being iterated
	 *
	 * @return bool  true on success, otherwise false
	 */
	abstract protected function updateSchedule(int $scheduleID);

	/**
	 * Updates resource associations in schedules.
	 *
	 * @return bool  true on success, otherwise false
	 */
	private function updateSchedules()
	{
		$query = $this->_db->getQuery(true);
		$query->select('id');
		$query->from('#__organizer_schedules');
		$this->_db->setQuery($query);

		if (!$scheduleIDs = Helpers\OrganizerHelper::executeQuery('loadColumn', []))
		{
			return true;
		}

		foreach ($scheduleIDs as $scheduleID)
		{
			$this->updateSchedule($scheduleID);
		}

		return true;
	}

	/**
	 * Updates resource associations in a schedule for groups and rooms, which are structurally identical.
	 *
	 * @param   int     $scheduleID  the id of the schedule being iterated
	 * @param   string  $context     the named index for the resource (groups|rooms)
	 *
	 * @return bool  true on success, otherwise false
	 */
	protected function updateEndResource(int $scheduleID, string $context)
	{
		if (!in_array($context, ['groups', 'rooms']))
		{
			return false;
		}

		$schedule = new Tables\Schedules();

		if (!$schedule->load($scheduleID))
		{
			return true;
		}

		$instances = json_decode($schedule->schedule, true);
		$mergeID   = $this->selected[0];
		$relevant  = false;

		foreach ($instances as $instanceID => $persons)
		{
			foreach ($persons as $personID => $data)
			{
				if (!$relevantRooms = array_intersect($data[$context], $this->selected))
				{
					continue;
				}

				$relevant = true;

				// Unset all relevant to avoid conditional and unique handling
				foreach (array_keys($relevantRooms) as $relevantIndex)
				{
					unset($instances[$instanceID][$personID][$context][$relevantIndex]);
				}

				// Put the merge id in/back in
				$instances[$instanceID][$personID][$context][] = $mergeID;

				// Resequence to avoid JSON encoding treating the array as associative (object)
				$instances[$instanceID][$personID][$context]
					= array_values($instances[$instanceID][$personID][$context]);
			}
		}

		if ($relevant)
		{
			$schedule->schedule = json_encode($instances);

			return $schedule->store();
		}

		return true;
	}
}
