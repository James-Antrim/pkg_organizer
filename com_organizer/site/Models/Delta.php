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

use Organizer\Helpers\OrganizerHelper;
use Organizer\Tables;

class Delta extends BaseModel
{
	private $modified;

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

		OrganizerHelper::executeQuery('execute');
	}
}