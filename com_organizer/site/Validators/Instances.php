<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Validators;

use Organizer\Helpers;
use Organizer\Tables;
use SimpleXMLElement;

/**
 * Provides functions for XML lesson validation and modeling.
 */
class Instances extends Helpers\ResourceHelper
{
	// Occurrence values
	private const NO = 0, VACATION = 'F';

	/**
	 * Adds the data for locating the missing room information to the warnings.
	 *
	 * @param   Schedule  $model       the model for the schedule being validated
	 * @param   string    $untisID     the untis id of the unit being iterated
	 * @param   array     $invalidIDs  the untis ids of rooms which proved to be invalid
	 */
	private static function addInvalidRoomData(Schedule $model, string $untisID, array $invalidIDs)
	{
		if (empty($model->warnings['IIR']))
		{
			$model->warnings['IIR'] = [];
		}

		if (empty($model->warnings['IIR'][$untisID]))
		{
			$model->warnings['IIR'][$untisID] = $invalidIDs;
		}
		else
		{
			$invalidIDs                       = array_diff($invalidIDs, $model->warnings['IIR'][$untisID]);
			$model->warnings['IIR'][$untisID] = array_merge($model->warnings['IIR'][$untisID], $invalidIDs);
		}
	}

	/**
	 * Adds the data for locating the missing room information to the warnings.
	 *
	 * @param   Schedule  $model      the model for the schedule being validated
	 * @param   string    $untisID    the untis id of the unit being iterated
	 * @param   int       $currentDT  the current date time in the iteration
	 * @param   int       $periodNo   the period number of the grid to look for times in
	 */
	private static function addMissingRoomData(Schedule $model, string $untisID, int $currentDT, int $periodNo)
	{
		if (empty($model->warnings['IMR']))
		{
			$model->warnings['IMR'] = [];
		}

		if (empty($model->warnings['IMR'][$untisID]))
		{
			$model->warnings['IMR'][$untisID] = [];
		}

		$dow = strtoupper(date('l', $currentDT));
		$dow = Helpers\Languages::_($dow);
		if (empty($model->warnings['IMR'][$untisID][$dow]))
		{
			$model->warnings['IMR'][$untisID][$dow] = [];
		}

		$date = date('Y-m-d', $currentDT);
		if (empty($model->warnings['IMR'][$untisID][$dow][$periodNo]))
		{
			$model->warnings['IMR'][$untisID][$dow][$periodNo] = [$date];
		}
		else
		{
			$model->warnings['IMR'][$untisID][$dow][$periodNo][] = $date;
		}
	}

	/**
	 * Retrieves the appropriate block id from the database, creating the entry as necessary.
	 *
	 * @param   SimpleXMLElement  $node         the node being validated
	 * @param   string            $currentDate  the current date being iterated
	 *
	 * @return int the id of the block
	 */
	private static function getBlockID(SimpleXMLElement $node, string $currentDate): int
	{
		$rawEndTime   = trim((string) $node->assigned_endtime);
		$rawStartTime = trim((string) $node->assigned_starttime);
		$endTime      = preg_replace('/([\d]{2})$/', ':${1}:00', $rawEndTime);
		$startTime    = preg_replace('/([\d]{2})$/', ':${1}:00', $rawStartTime);

		$block     = new Tables\Blocks();
		$blockData = [
			'date'      => $currentDate,
			'dow'       => date('w', strtotime($currentDate)),
			'startTime' => $startTime,
			'endTime'   => $endTime
		];

		if (!$block->load($blockData))
		{
			$block->save($blockData);
		}

		return $block->id;
	}

	/**
	 * Sets associations between an instance person association and its groups.
	 *
	 * @param   Schedule  $model       the model for the schedule being validated
	 * @param   string    $untisID     the untis id of the unit being iterated
	 * @param   int       $instanceID  the id of the instance being validated
	 * @param   int       $assocID     the id of the instance person association with which the groups are to be associated
	 *
	 * @return void
	 */
	private static function setGroups(Schedule $model, string $untisID, int $instanceID, int $assocID)
	{
		$instances = &$model->instances;
		$unit      = $model->units->$untisID;
		$personID  = $unit->personID;
		$groups    = $unit->groups;

		if (empty($instances[$instanceID][$personID]['groups']))
		{
			$newGroups = $groups;

			$instances[$instanceID][$personID]['groups'] = $newGroups;
		}
		else
		{
			$newGroups = array_diff($unit->groups, $instances[$instanceID][$personID]['groups']);
			$instances[$instanceID][$personID]['groups']
			           = array_merge($instances[$instanceID][$personID]['groups'], $newGroups);
		}

		foreach ($newGroups as $groupID)
		{
			$instanceGroup = ['assocID' => $assocID, 'groupID' => $groupID];
			$table         = new Tables\InstanceGroups();

			if (!$table->load($instanceGroup))
			{
				$table->save($instanceGroup);
			}
		}
	}

	/**
	 * Retrieves the resource id using the Untis ID. Creates the resource id if unavailable.
	 *
	 * @param   Schedule          $model        the model for the schedule being validated
	 * @param   SimpleXMLElement  $node         the node being validated
	 * @param   string            $untisID      the untis id of the unit being iterated
	 * @param   string            $currentDate  the current date being iterated
	 *
	 * @return void modifies the model, setting the id property of the resource
	 */
	public static function setInstance(Schedule $model, SimpleXMLElement $node, string $untisID, string $currentDate)
	{
		$unit = $model->units->$untisID;

		// Update the actual run dates of the unit
		if (!$unit->effEndDate or $unit->effEndDate < $currentDate)
		{
			$unit->effEndDate = $currentDate;
		}

		if (!$unit->effStartDate or $unit->effStartDate > $currentDate)
		{
			$unit->effStartDate = $currentDate;
		}

		$methodID = empty($unit->methodID) ? null : $unit->methodID;
		$instance = [
			'blockID' => self::getBlockID($node, $currentDate),
			'eventID' => $unit->eventID,
			'unitID'  => $unit->id
		];

		$table = new Tables\Instances();

		if ($table->load($instance))
		{
			$table->methodID = $methodID;
			$table->store();
		}
		else
		{
			$instance['methodID'] = $methodID;
			$table->save($instance);
		}

		$instanceID = $table->id;
		$instances  = &$model->instances;

		if (empty($instances[$instanceID]))
		{
			$instances[$instanceID] = [];
		}

		self::setPerson($model, $untisID, $instanceID);
	}

	/**
	 * Sets an instance person association.
	 *
	 * @param   Schedule  $model       the model for the schedule being validated
	 * @param   string    $untisID     the untis id of the unit being iterated
	 * @param   int       $instanceID  the id of the instance being validated
	 *
	 * @return void
	 */
	private static function setPerson(Schedule $model, string $untisID, int $instanceID)
	{
		$instances = &$model->instances;
		$unit      = $model->units->$untisID;
		$personID  = $unit->personID;
		if (empty($instances[$instanceID][$personID]))
		{
			$instances[$instanceID][$personID] = [];
		}

		$instancePerson = ['instanceID' => $instanceID, 'personID' => $personID];
		$roleID         = $unit->roleID;

		$table = new Tables\InstancePersons();

		if ($table->load($instancePerson))
		{
			$table->roleID = $roleID;
			$table->store();
		}
		else
		{
			$instancePerson['roleID'] = $roleID;
			$table->save($instancePerson);
		}

		$assocID  = $table->id;
		$personID = $unit->personID;

		// The role defaults to 1 and is 1 in most cases, deviations are recorded.
		$instances[$instanceID][$personID]['roleID'] = $roleID;
		self::setGroups($model, $untisID, $instanceID, $assocID);
		self::setRooms($model, $untisID, $instanceID, $assocID);
	}

	/**
	 * Sets associations between an instance person association and its groups.
	 *
	 * @param   Schedule  $model       the model for the schedule being validated
	 * @param   string    $untisID     the untis id of the unit being iterated
	 * @param   int       $instanceID  the id of the instance being validated
	 * @param   int       $assocID     the id of the instance person association with which the groups are to be associated
	 *
	 * @return void
	 */
	private static function setRooms(Schedule $model, string $untisID, int $instanceID, int $assocID)
	{
		$instances = &$model->instances;
		$unit      = $model->units->$untisID;
		$personID  = $unit->personID;
		$rooms     = $unit->rooms;

		if (empty($instances[$instanceID][$personID]['rooms']))
		{
			$newRooms = $rooms;

			$instances[$instanceID][$personID]['rooms'] = $newRooms;
		}
		else
		{
			$newRooms = array_diff($unit->rooms, $instances[$instanceID][$personID]['rooms']);
			$instances[$instanceID][$personID]['rooms']
			          = array_merge($instances[$instanceID][$personID]['rooms'], $newRooms);
		}

		foreach ($newRooms as $roomID)
		{
			$instanceRoom = ['assocID' => $assocID, 'roomID' => $roomID];
			$table        = new Tables\InstanceRooms();

			if (!$table->load($instanceRoom))
			{
				$table->save($instanceRoom);
			}
		}
	}

	/**
	 * Iterates over possible occurrences and validates them
	 *
	 * @param   Schedule          $model        the model for the schedule being validated
	 * @param   SimpleXMLElement  $node         the node being validated
	 * @param   string            $untisID      the untis id of the unit being iterated
	 * @param   array             $occurrences  an array of 'occurrences'
	 * @param   bool              $valid        whether or not the planning unit is valid (for purposes of saving)
	 *
	 * @return void
	 */
	public static function validateCollection(
		Schedule $model,
		SimpleXMLElement $node,
		string $untisID,
		array $occurrences,
		bool $valid
	)
	{
		if (!$node->count())
		{
			return;
		}

		// Instance templates for regular units or actual instances for sporadic units
		$instances = $node->children();
		$unit      = $model->units->$untisID;
		$currentDT = $unit->startDT;

		foreach ($occurrences as $occurrence)
		{
			// Instances are relevant if they occur after the creation of the schedule itself.
			$relevant = $currentDT > $model->dateTime;

			// Instances are irrelevant on vacation days or outside scope
			$irrelevant = ($occurrence == self::NO or $occurrence == self::VACATION);

			if ($relevant and !$irrelevant)
			{
				foreach ($instances as $instance)
				{
					self::validateInstance($model, $instance, $untisID, $currentDT, $valid);
				}
			}

			$currentDT = strtotime('+1 day', $currentDT);
		}
	}

	/**
	 * Validates instance dates and rooms.
	 *
	 * @param   Schedule          $model      the model for the schedule being validated
	 * @param   SimpleXMLElement  $node       the node being validated
	 * @param   string            $untisID    the untis id of the unit being iterated
	 * @param   int               $currentDT  the current date time in the iteration
	 * @param   bool              $valid      whether or not the planning unit is valid (for purposes of saving)
	 *
	 * @return void errors are added to the model's errors property
	 */
	private static function validateInstance(
		Schedule $model,
		SimpleXMLElement $node,
		string $untisID,
		int $currentDT,
		bool $valid
	)
	{
		// Current date not applicable for this instance
		if (trim((string) $node->assigned_day) != date('w', $currentDT))
		{
			return;
		}

		// Sporadic events have specific dates assigned to them.
		$specificDate = strtotime(trim((string) $node->assigned_date));

		// The event is sporadic and does not occur on the date being currently iterated
		if (!empty($specificDate) and $specificDate != $currentDT)
		{
			return;
		}

		$periodNo    = trim((string) $node->assigned_period);
		$unit        = $model->units->$untisID;
		$unit->rooms = [];

		if (!$roomAttribute = trim((string) $node->assigned_room[0]['id']))
		{
			self::addMissingRoomData($model, $untisID, $currentDT, $periodNo);
		}
		else
		{
			$invalidIDs = [];
			$rooms      = $model->rooms;
			$roomIDs    = explode(' ', str_replace('RM_', '', strtoupper($roomAttribute)));

			foreach ($roomIDs as $roomID)
			{
				if (empty($rooms->$roomID))
				{
					$invalidIDs[] = $roomID;
					continue;
				}

				$unit->rooms[] = $rooms->$roomID->id;
			}

			if (count($invalidIDs))
			{
				self::addInvalidRoomData($model, $untisID, $invalidIDs);
			}
		}

		if ($valid)
		{
			$currentDate = date('Y-m-d', $currentDT);
			self::setInstance($model, $node, $untisID, $currentDate);
		}
	}
}
