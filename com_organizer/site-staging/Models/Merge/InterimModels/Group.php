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
 * Class which manages stored group data.
 */
class Group extends MergeModel implements ScheduleResource
{
	/**
	 * Updates key references to the entry being merged.
	 *
	 * @return bool  true on success, otherwise false
	 */
	protected function updateReferences()
	{
		if (!$this->updateReferencingTable('pools'))
		{
			return false;
		}

		return $this->updateInstanceGroups();
	}

	/**
	 * Updates the instance groups table to reflect the merge of the groups.
	 *
	 * @return bool true on success, otherwise false;
	 */
	private function updateInstanceGroups()
	{
		if (!$relevantAssocs = $this->getAssociatedResourceIDs('assocID', 'instance_groups'))
		{
			return true;
		}

		$mergeID = reset($this->selected);

		foreach ($relevantAssocs as $assocID)
		{
			$delta       = '';
			$modified    = '';
			$existing    = new Tables\InstanceGroups();
			$entryExists = $existing->load(['assocID' => $assocID, 'groupID' => $mergeID]);

			foreach ($this->selected as $groupID)
			{
				$igTable        = new Tables\InstanceGroups();
				$loadConditions = ['assocID' => $assocID, 'groupID' => $groupID];
				if (!$igTable->load($loadConditions))
				{
					continue;
				}

				if ($igTable->modified > $modified)
				{
					$delta    = $igTable->delta;
					$modified = $igTable->modified;
				}

				if ($entryExists)
				{
					if ($existing->id !== $igTable->id)
					{
						$igTable->delete();
					}

					continue;
				}

				$entryExists = true;
				$existing    = $igTable;
			}

			$existing->delta    = $delta;
			$existing->groupID  = $mergeID;
			$existing->modified = $modified;
			if (!$existing->store())
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Processes the data for an individual schedule
	 *
	 * @param   Tables\Schedules  $schedule  the schedule being processed
	 *
	 * @return bool true if the schedule was changed, otherwise false
	 */
	public function updateSchedule($schedule)
	{
		$instances = json_decode($schedule->schedule, true);
		$mergeID   = reset($this->selected);
		$relevant  = false;

		foreach ($instances as $instanceID => $persons)
		{
			foreach ($persons as $personID => $data)
			{
				if (!$relevantGroups = array_intersect($data['groups'], $this->selected))
				{
					continue;
				}

				$relevant = true;

				// Unset all relevant to avoid conditional and unique handling
				foreach (array_keys($relevantGroups) as $relevantIndex)
				{
					unset($instances[$instanceID][$personID]['groups'][$relevantIndex]);
				}

				// Put the merge id in/back in
				$instances[$instanceID][$personID]['groups'][] = $mergeID;

				// Resequence to avoid JSON encoding treating the array as associative (object)
				$instances[$instanceID][$personID]['groups']
					= array_values($instances[$instanceID][$personID]['groups']);
			}
		}

		if ($relevant)
		{
			$schedule->schedule = json_encode($instances);

			return true;
		}

		return false;
	}
}
