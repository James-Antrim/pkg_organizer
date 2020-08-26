<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers\Validators;

use Organizer\Helpers;
use Organizer\Tables;
use SimpleXMLElement;
use stdClass;

/**
 * Provides functions for XML lesson validation and modeling.
 */
class Instances extends Helpers\ResourceHelper
{
	const NO = 0;
	const VACATION = 'F';

	/**
	 * Adds the data for locating the missing room information to the warnings.
	 *
	 * @param   object  $model       the model for the schedule being validated
	 * @param   int     $untisID     the id of the lesson being iterated
	 * @param   array   $invalidIDs  the untis ids of rooms which proved to be invalid
	 */
	private static function addInvalidRoomData($model, $untisID, $invalidIDs)
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
	 * @param   object  $model      the model for the schedule being validated
	 * @param   int     $untisID    the id of the lesson being iterated
	 * @param   int     $currentDT  the current date time in the iteration
	 * @param   int     $periodNo   the period number of the grid to look for times in
	 */
	private static function addMissingRoomData($model, $untisID, $currentDT, $periodNo)
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
	private static function getBlockID($node, $currentDate)
	{
		$rawEndTime   = trim((string) $node->assigned_endtime);
		$rawStartTime = trim((string) $node->assigned_starttime);
		$endTime      = preg_replace('/([\d]{2})$/', ':${1}:00', $rawEndTime);
		$startTime    = preg_replace('/([\d]{2})$/', ':${1}:00', $rawStartTime);

		$blocks    = new Tables\Blocks();
		$blockData = ['date' => $currentDate, 'startTime' => $startTime, 'endTime' => $endTime];
		if (!$blocks->load($blockData))
		{
			$blocks->save($blockData);
		}

		return $blocks->id;
	}

	/**
	 * Processes instance information for the new schedule format
	 *
	 * @param   object            $model      the model for the schedule being validated
	 * @param   SimpleXMLElement  $node       the node being validated
	 * @param   int               $untisID    the id of the lesson being iterated
	 * @param   string            $currentDT  the current datetime value
	 *
	 * @return void
	 */
	private static function processInstance($model, $node, $untisID, $currentDT)
	{
		$calendar    = $model->schedule->calendar;
		$currentDate = date('Y-m-d', $currentDT);

		// New format calendar items are created as necessary
		if (!isset($calendar->$currentDate))
		{
			$calendar->$currentDate = new stdClass();
		}

		$endTime   = trim((string) $node->assigned_endtime);
		$startTime = trim((string) $node->assigned_starttime);

		$times = $startTime . '-' . $endTime;
		if (!isset($calendar->$currentDate->$times))
		{
			$calendar->$currentDate->$times = new stdClass();
		}

		if (!isset($calendar->$currentDate->$times->$untisID))
		{
			$entry                 = new stdClass();
			$entry->delta          = '';
			$entry->configurations = [];

			$calendar->$currentDate->$times->$untisID = $entry;
		}

		$unit = $model->units->$untisID;

		$config            = new stdClass();
		$config->lessonID  = $untisID;
		$config->subjectID = $unit->eventID;
		$config->teachers  = new stdClass();
		$config->rooms     = new stdClass();

		$config->teachers->{$unit->personID} = '';

		foreach ($unit->rooms as $roomID)
		{
			$config->rooms->{$roomID} = '';
		}

		$entry         = $calendar->$currentDate->$times->$untisID;
		$existingIndex = null;

		if (!empty($entry->configurations))
		{
			$compConfig = null;

			foreach ($entry->configurations as $configIndex)
			{
				$tempConfig = json_decode($model->schedule->configurations[$configIndex]);

				if ($tempConfig->subjectID == $config->subjectID)
				{
					$compConfig    = $tempConfig;
					$existingIndex = $configIndex;
					break;
				}
			}

			if ($compConfig)
			{
				foreach ($compConfig->teachers as $teacherID => $emptyDelta)
				{
					$config->teachers->$teacherID = $emptyDelta;
				}

				foreach ($compConfig->rooms as $roomID => $emptyDelta)
				{
					$config->rooms->$roomID = $emptyDelta;
				}
			}
		}

		$jsonConfig = json_encode($config);

		if ($existingIndex)
		{
			$model->schedule->configurations[$existingIndex] = $jsonConfig;

			return;
		}

		$model->schedule->configurations[] = $jsonConfig;

		$configKeys  = array_keys($model->schedule->configurations);
		$configIndex = end($configKeys);

		$entry->configurations[] = $configIndex;
	}

	/**
	 * Sets associations between an instance person association and its groups.
	 *
	 * @param   object  $model       the model for the schedule being validated
	 * @param   int     $untisID     the id of the lesson being iterated
	 * @param   int     $instanceID  the id of the instance being validated
	 * @param   int     $assocID     the id of the instance person association with which the groups are to be associated
	 *
	 * @return void
	 */
	private static function setGroups($model, $untisID, $instanceID, $assocID)
	{
		$instances = &$model->instances;
		$unit      = $model->units->$untisID;
		$personID  = $unit->personID;
		$groups    = $unit->groups;

		if (empty($instances[$instanceID][$personID]['groups']))
		{
			$newGroups                                   = $groups;
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
			$table         = new Tables\InstanceGroups;

			if ($table->load($instanceGroup))
			{
				if (!empty($table->delta))
				{
					$table->set('delta', '');
					$table->store();
				}
			}
			else
			{
				$instanceGroup['delta'] = 'new';
				$table->save($instanceGroup);
			}
		}
	}

	/**
	 * Retrieves the resource id using the Untis ID. Creates the resource id if unavailable.
	 *
	 * @param   object            $model        the model for the schedule being validated
	 * @param   SimpleXMLElement  $node         the node being validated
	 * @param   int               $untisID      the id of the lesson being iterated
	 * @param   string            $currentDate  the current date being iterated
	 *
	 * @return void modifies the model, setting the id property of the resource
	 */
	public static function setInstance($model, $node, $untisID, $currentDate)
	{
		$unit     = $model->units->$untisID;
		$methodID = empty($unit->methodID) ? null : $unit->methodID;
		$instance = [
			'blockID' => self::getBlockID($node, $currentDate),
			'eventID' => $unit->eventID,
			'unitID'  => $unit->id
		];
		$table    = new Tables\Instances;

		if ($table->load($instance))
		{
			$altered = false;

			if ($table->methodID != $methodID)
			{
				$table->set('methodID', $methodID);
				$altered = true;
			}

			if ($altered)
			{
				$table->set('delta', 'changed');
				$table->store();
			}
			elseif (!empty($table->delta))
			{
				$table->set('delta', '');
				$table->store();
			}

		}
		else
		{
			$instance['delta']    = 'new';
			$instance['methodID'] = $methodID;
			$table->save($instance);
		}

		$instanceID = $table->id;
		$instances  = &$model->instances;

		if (empty($instances[$instanceID]))
		{
			$instances[$instanceID] = [];
		}

		self::setInstancePerson($model, $untisID, $instanceID);

		return;
	}

	/**
	 * Sets an instance person association.
	 *
	 * @param   object  $model       the model for the schedule being validated
	 * @param   int     $untisID     the id of the lesson being iterated
	 * @param   int     $instanceID  the id of the instance being validated
	 *
	 * @return void
	 */
	private static function setInstancePerson($model, $untisID, $instanceID)
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
		$table          = new Tables\InstancePersons();
		if ($table->load($instancePerson))
		{
			$altered = false;

			if ($table->roleID != $roleID)
			{
				$table->roleID = $roleID;
				$altered       = true;
			}

			if ($altered)
			{
				$table->set('delta', 'changed');
				$table->store();
			}
			elseif (!empty($table->delta))
			{
				$table->set('delta', '');
				$table->store();
			}

		}
		else
		{
			$instancePerson['delta']  = 'new';
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
	 * @param   object  $model       the model for the schedule being validated
	 * @param   int     $untisID     the id of the lesson being iterated
	 * @param   int     $instanceID  the id of the instance being validated
	 * @param   int     $assocID     the id of the instance person association with which the groups are to be associated
	 *
	 * @return void
	 */
	private static function setRooms($model, $untisID, $instanceID, $assocID)
	{
		$instances = &$model->instances;
		$unit      = $model->units->$untisID;
		$personID  = $unit->personID;
		$rooms     = $unit->rooms;

		if (empty($instances[$instanceID][$personID]['rooms']))
		{
			$newRooms                                   = $rooms;
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
			$table        = new Tables\InstanceRooms;

			if ($table->load($instanceRoom))
			{
				if (!empty($table->delta))
				{
					$table->set('delta', '');
					$table->store();
				}
			}
			else
			{
				$instanceRoom['delta'] = 'new';
				$table->save($instanceRoom);
			}
		}
	}

	/**
	 * Iterates over possible occurrences and validates them
	 *
	 * @param   object            $model        the model for the schedule being validated
	 * @param   SimpleXMLElement  $node         the node being validated
	 * @param   int               $untisID      the id of the lesson being iterated
	 * @param   array             $occurrences  an array of 'occurrences'
	 * @param   bool              $valid        whether or not the planning unit is valid (for purposes of saving)
	 *
	 * @return void
	 */
	public static function validateCollection($model, $node, $untisID, $occurrences, $valid)
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
			// Untis uses F for vacation days and 0 for any other date restriction
			$irrelevant = ($occurrence == self::NO or $occurrence == self::VACATION);
			if (!$irrelevant)
			{
				foreach ($instances as $instance)
				{
					self::validateInstance($model, $instance, $untisID, $currentDT, $valid);
				}
			}

			$currentDT = strtotime('+1 day', $currentDT);
		}

		return;
	}

	/**
	 * Validates instance dates and rooms.
	 *
	 * @param   object            $model      the model for the schedule being validated
	 * @param   SimpleXMLElement  $node       the node being validated
	 * @param   int               $untisID    the id of the lesson being iterated
	 * @param   int               $currentDT  the current date time in the iteration
	 * @param   bool              $valid      whether or not the planning unit is valid (for purposes of saving)
	 *
	 * @return void errors are added to the model's errors property
	 */
	private static function validateInstance($model, $node, $untisID, $currentDT, $valid)
	{
		// Current date not applicable for this instance
		if (trim((string) $node->assigned_day) != date('w', $currentDT))
		{
			return;
		}

		// Sporadic events have specific dates assigned to them.
		$assigned_date = strtotime(trim((string) $node->assigned_date));

		// The event is sporadic and does not occur on the date being currently iterated
		if (!empty($assigned_date) and $assigned_date != $currentDT)
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
			//self::setInstance($model, $node, $untisID, $currentDT);
			self::processInstance($model, $node, $untisID, $currentDT);
		}
	}
}
