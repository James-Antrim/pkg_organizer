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

use Joomla\CMS\Factory;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored room data.
 */
class Room extends MergeModel
{
	/**
	 * Activates rooms by id if a selection was made, otherwise by use in the instance_rooms table.
	 *
	 * @return bool true on success, otherwise false
	 */
	public function activate()
	{
		$this->selected = Helpers\Input::getSelectedIDs();
		$this->authorize();

		// Explicitly selected resources
		if ($this->selected)
		{
			foreach ($this->selected as $selectedID)
			{
				$room = new Tables\Rooms();

				if ($room->load($selectedID))
				{
					$room->active = 1;
					$room->store();
					continue;
				}

				return false;
			}

			return true;
		}

		// Implicitly used resources
		$dbo = Factory::getDbo();

		$subQuery = $dbo->getQuery(true);
		$subQuery->select('DISTINCT roomID')->from('#__organizer_instance_rooms');

		$query = $dbo->getQuery(true);
		$query->update('#__organizer_rooms')->set('active = 1')->where("id IN ($subQuery)");
		$dbo->setQuery($query);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Authorizes the user.
	 *
	 * @return void
	 */
	protected function authorize()
	{
		if (!Helpers\Can::manage('facilities'))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * Deactivates rooms by id if a selection was made, otherwise by lack of use in the instance_rooms table.
	 *
	 * @return bool true on success, otherwise false
	 */
	public function deactivate()
	{
		$this->selected = Helpers\Input::getSelectedIDs();
		$this->authorize();

		// Explicitly selected resources
		if ($this->selected)
		{
			foreach ($this->selected as $selectedID)
			{
				$room = new Tables\Rooms();

				if ($room->load($selectedID))
				{
					$room->active = 0;
					$room->store();
					continue;
				}

				return false;
			}

			return true;
		}

		// Implicitly unused resources
		$dbo = Factory::getDbo();

		$subQuery = $dbo->getQuery(true);
		$subQuery->select('DISTINCT roomID')->from('#__organizer_instance_rooms');

		$query = $dbo->getQuery(true);
		$query->update('#__organizer_rooms')->set('active = 0')->where("id NOT IN ($subQuery)");
		$dbo->setQuery($query);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Rooms A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Rooms;
	}

	/**
	 * Updates the resource dependent associations
	 *
	 * @return bool  true on success, otherwise false
	 */
	protected function updateAssociations()
	{
		if (!$this->updateDirectAssociation('monitors'))
		{
			return false;
		}

		if (!$this->updateLessonConfigurations('rooms'))
		{
			return false;
		}

		return $this->updateAssocAssociations();
	}

	/**
	 * Updates resource associations in an old format schedule.
	 *
	 * @param   int  $scheduleID  the id of the schedule being iterated
	 *
	 * @return bool  true on success, otherwise false
	 */
	protected function updateOldSchedule(int $scheduleID)
	{
		$query = $this->_db->getQuery(true);
		$query->select('schedule')
			->from('#__thm_organizer_schedules')
			->where("id = $scheduleID");
		$this->_db->setQuery($query);

		if (!$schedule = Helpers\OrganizerHelper::executeQuery('loadResult', ''))
		{
			return true;
		}

		// Zombie, but old so just delete
		if (strpos($schedule, '"configurations":{}') !== false)
		{
			$query = $this->_db->getQuery(true);
			$query->delete('#__thm_organizer_schedules')->where("id = $scheduleID");
			$this->_db->setQuery($query);

			return (bool) Helpers\OrganizerHelper::executeQuery('execute', false);
		}

		$schedule = json_decode($schedule, true);

		// The schedule is invalid
		if (empty($schedule['configurations']))
		{
			return false;
		}

		$mergeID = $this->selected[0];

		foreach ($schedule['configurations'] as $index => $configuration)
		{
			$inConfig      = false;
			$configuration = json_decode($configuration);

			foreach ($configuration->rooms as $roomID => $delta)
			{
				if (in_array($roomID, $this->selected))
				{
					$inConfig = true;

					// Whether old or new high probability of having to overwrite an attribute this enables standard handling.
					unset($configuration->rooms->$roomID);
					$configuration->rooms->$mergeID = $delta;
				}
			}

			if ($inConfig)
			{
				$schedule->configurations[$index] = json_encode($configuration);
			}
		}

		$query = $this->_db->getQuery(true);
		$query->updated('#__thm_organizer_schedules')
			->set('')
			->where("id = $scheduleID");
		$this->_db->setQuery($query);
	}

	/**
	 * Updates resource associations in a schedule.
	 *
	 * @param   int  $scheduleID  the id of the schedule being iterated
	 *
	 * @return bool  true on success, otherwise false
	 */
	protected function updateSchedule(int $scheduleID)
	{
		// This would otherwise be identical to groups
		return $this->updateEndResource($scheduleID, 'rooms');
	}
}
