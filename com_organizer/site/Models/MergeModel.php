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
use Organizer\Adapters\Database;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class provides methods for merging resources. Resource specific tasks are implemented in the extending classes.
 */
abstract class MergeModel extends BaseModel
{
	/**
	 * Gets the resource ids associated with persons in association tables.
	 *
	 * @param   string  $table     the unique portion of the table name
	 * @param   string  $fkColumn  the name of the fk column referencing the other resource
	 *
	 * @return array the ids of the resources associated
	 */
	protected function getReferencedIDs(string $table, string $fkColumn): array
	{
		$selectedIDs = implode(',', $this->selected);
		$query       = Database::getQuery();
		$query->select("DISTINCT $fkColumn")
			->from("#__organizer_$table")
			->where("personID IN ($selectedIDs)")
			->order("$fkColumn");
		Database::setQuery($query);

		return Database::loadIntColumn();
	}

	/**
	 * Merges resource entries and cleans association tables.
	 *
	 * @return bool  true on success, otherwise false
	 * @throws Exception
	 */
	public function merge(): bool
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
	 * Updates the associations table, ensuring that the merge id is the only one referenced and that there is only one
	 * association per organization.
	 *
	 * If the database at some point allows unique keys over multiple nullable columns and organizer uses such a unique
	 * key for this table, this function will have to be rewritten with more conditionals.
	 *
	 * @return bool
	 */
	protected function updateAssociationsReferences(): bool
	{
		$fkColumn  = strtolower($this->name) . 'ID';
		$query     = Database::getQuery(true);
		$updateIDs = implode(', ', $this->selected);
		$query->select("id, $fkColumn, organizationID")
			->from("#__organizer_associations")
			->where("$fkColumn IN ($updateIDs)")
			->order("id");
		Database::setQuery($query);

		if (!$results = Database::loadAssocList())
		{
			return true;
		}

		$association     = new Tables\Associations();
		$mergeID         = $this->selected[0];
		$organizationIDs = [];

		foreach ($results as $result)
		{
			// The association to this organization has already been processed.
			if (in_array($result['organizationID'], $organizationIDs) and !$association->delete($result['id']))
			{
				return false;
			}

			$organizationIDs[] = $result['organizationID'];

			// The association is correct as is.
			if ($result[$fkColumn] == $mergeID)
			{
				continue;
			}

			$entry = ['id' => $result['id'], $fkColumn => $mergeID, 'organizationID' => $result['organizationID']];

			$entry[$fkColumn] = $mergeID;
			if (!$association->save($entry))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Updates an instance person association with persons or rooms.
	 *
	 * @return bool  true on success, otherwise false
	 */
	protected function updateIPReferences(): bool
	{
		$fkColumn    = strtolower($this->name) . 'ID';
		$query       = Database::getQuery();
		$tableSuffix = strtolower($this->name) . 's';
		$updateIDs   = implode(', ', $this->selected);
		$query->select('*')
			->from("#__organizer_instance_$tableSuffix")
			->where("$fkColumn IN ($updateIDs)")
			->order('assocID, modified');
		Database::setQuery($query);

		if (!$results = Database::loadAssocList())
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
			$nextIndex  = $nextIndex ?: $index + 1;
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
	abstract protected function updateReferences(): bool;

	/**
	 * Updates an association where the associated resource itself has a fk reference to the resource being merged.
	 *
	 * @param   string  $table  the unique part of the table name
	 *
	 * @return bool  true on success, otherwise false
	 */
	protected function updateReferencingTable(string $table): bool
	{
		$fkColumn  = strtolower($this->name) . 'ID';
		$mergeID   = $this->selected[0];
		$query     = Database::getQuery();
		$updateIDs = implode(', ', $this->selected);
		$query->update("#__organizer_$table")->set("$fkColumn = $mergeID")->where("$fkColumn IN ($updateIDs)");
		Database::setQuery($query);

		return Database::execute();
	}

	/**
	 * Updates resource associations in a schedule instance.
	 *
	 * @param   array  &$instance  the instance being iterated
	 * @param   int     $mergeID   the id onto which the entries will be merged
	 *
	 * @return bool true if the instance has been updated, otherwise false
	 */
	private function updateInstance(array &$instance, int $mergeID): bool
	{
		$context  = strtolower($this->name) . 's';
		$relevant = false;

		foreach ($instance as $personID => $resources)
		{
			// Array intersect keeps relevant keys from array one.
			if (!$relevantResources = array_intersect($resources[$context], $this->selected))
			{
				continue;
			}

			$relevant = true;

			// Unset all relevant indexes to avoid conditional/unique handling
			foreach (array_keys($relevantResources) as $relevantIndex)
			{
				unset($instance[$personID][$context][$relevantIndex]);
			}

			// Put the merge id in/back in
			$instance[$personID][$context][] = $mergeID;

			// Resequence to avoid JSON encoding treating the array as associative (object)
			$instance[$personID][$context] = array_values($instance[$personID][$context]);
		}

		return $relevant;
	}

	/**
	 * Updates resource associations in a schedule.
	 *
	 * @param   int  $scheduleID  the id of the schedule being iterated
	 *
	 * @return void
	 */
	private function updateSchedule(int $scheduleID)
	{
		$context = strtolower($this->name);

		// Only these resources are referenced in saved schedules.
		if (!in_array($context, ['group', 'person', 'room']))
		{
			return;
		}

		$schedule = new Tables\Schedules();

		if (!$schedule->load($scheduleID))
		{
			return;
		}

		$instances = json_decode($schedule->schedule, true);
		$mergeID   = $this->selected[0];
		$relevant  = false;

		foreach ($instances as $instanceID => $instance)
		{
			if (in_array($context, ['group', 'room']) and $this->updateInstance($instance, $mergeID))
			{
				$instances[$instanceID] = $instance;
				$relevant               = true;
			} // Person
			else
			{
				if (!$relevantPersons = array_intersect(array_keys($instance), $this->selected))
				{
					continue;
				}

				$relevant = true;
				ksort($relevantPersons);

				// Use the associations of the maximum personID (last added)
				$associations = [];

				foreach ($relevantPersons as $personID)
				{
					$associations = $instances[$instanceID][$personID];
					unset($instances[$instanceID][$personID]);
				}

				$instances[$instanceID][$mergeID] = $associations;
			}
		}

		if ($relevant)
		{
			$schedule->schedule = json_encode($instances);
			$schedule->store();
		}
	}

	/**
	 * Updates resource associations in schedules.
	 *
	 * @return bool  true on success, otherwise false
	 */
	private function updateSchedules(): bool
	{
		$query = Database::getQuery();
		$query->select('id')->from('#__organizer_schedules');
		Database::setQuery($query);

		if (!$scheduleIDs = Database::loadIntColumn())
		{
			return true;
		}

		foreach ($scheduleIDs as $scheduleID)
		{
			$this->updateSchedule($scheduleID);
		}

		return true;
	}
}
