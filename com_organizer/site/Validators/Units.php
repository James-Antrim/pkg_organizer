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

use Organizer\Adapters\Database;
use Organizer\Helpers;
use Organizer\Helpers\Languages;
use Organizer\Tables;
use SimpleXMLElement;
use stdClass;

/**
 * Provides functions for XML unit validation and persistence.
 */
class Units extends Helpers\ResourceHelper implements UntisXMLValidator
{
	/**
	 * Determines how the missing room attribute will be handled
	 *
	 * @param   Schedule  $model  the model for the schedule being validated
	 *
	 * @return void adds a message to the model warnings array
	 */
	private static function createInvalidRoomsMessages(Schedule $model)
	{
		foreach ($model->warnings['IIR'] as $untisID => $invalidRooms)
		{
			asort($invalidRooms);
			$invalidRooms = implode(', ', $invalidRooms);
			$pos          = strrpos(', ', $invalidRooms);
			if ($pos !== false)
			{
				$and          = Languages::_('ORGANIZER_AND');
				$invalidRooms = substr_replace($invalidRooms, " $and ", $pos, strlen($invalidRooms));
			}

			$model->warnings[] = sprintf(
				Languages::_('ORGANIZER_UNIT_ROOM_INCOMPLETE'),
				$untisID,
				$invalidRooms
			);
		}
		unset($model->warnings['IIR']);
	}

	/**
	 * Determines how the missing room attribute will be handled
	 *
	 * @param   Schedule  $model  the model for the schedule being validated
	 *
	 * @return void adds a message to the model warnings array
	 */
	private static function createMissingRoomsMessages(Schedule $model)
	{
		foreach ($model->warnings['IMR'] as $untisID => $DOWs)
		{
			foreach ($DOWs as $dow => $periods)
			{
				foreach ($periods as $periodNo => $missingDates)
				{
					if (count($missingDates) > 2)
					{
						$model->warnings[] = sprintf(
							Languages::_('ORGANIZER_UNIT_ROOMS_MISSING'),
							$untisID,
							$dow,
							$periodNo
						);
						continue;
					}

					$dates = implode(', ', $missingDates);
					$pos   = strrpos(', ', $dates);
					if ($pos !== false)
					{
						$and   = Languages::_('ORGANIZER_AND');
						$dates = substr_replace($dates, " $and ", $pos, strlen($dates));
					}

					$model->warnings[] = sprintf(
						Languages::_('ORGANIZER_UNIT_ROOMS_MISSING'),
						$untisID,
						$dates,
						$periodNo
					);
				}

			}
		}
		unset($model->warnings['IMR']);
	}

	/**
	 * Gets the id for a named role.
	 *
	 * @param   string  $role  the role as specified in the schedule
	 *
	 * @return int the id of the role, defaults to 1
	 */
	private static function getRoleID(string $role): int
	{
		$role = trim($role);

		if (empty($role) or !preg_match('/^[a-zA-Z]+$/', $role))
		{
			return 1;
		}

		$role  = strtoupper($role);
		$query = Database::getQuery(true);
		$query->select('id')->from('#__organizer_roles')->where("code = '$role'");
		Database::setQuery($query);

		return Database::loadInt(1);
	}

	/**
	 * Adjusts the temporal template ('occurrence' attribute) to the unit's actual dates.
	 *
	 * @param   Schedule          $model    the model for the schedule being validated
	 * @param   SimpleXMLElement  $node     the node being validated
	 * @param   string            $untisID  the untis id of the unit being iterated
	 *
	 * @return array   the occurrences string modeled by an array
	 */
	private static function getFilteredOccurrences(Schedule $model, SimpleXMLElement $node, string $untisID): array
	{
		$rawOccurrences = trim((string) $node->occurence);
		$unit           = $model->units->$untisID;

		// Increases the end value one day (Untis uses inclusive dates)
		$end = strtotime('+1 day', $unit->endDT);

		// 86400 is the number of seconds in a day 24 * 60 * 60
		$offset = floor(($unit->startDT - strtotime($model->schoolYear->startDate)) / 86400);
		$length = floor(($end - $unit->startDT) / 86400);

		$filteredOccurrences = substr($rawOccurrences, $offset, $length);

		// Change occurrences from a string to an array of the appropriate length for iteration
		return empty($filteredOccurrences) ? [] : str_split($filteredOccurrences);
	}

	/**
	 * Retrieves the resource id using the Untis ID. Creates the resource id if unavailable.
	 *
	 * @param   Schedule  $model  the model for the schedule being validated
	 * @param   string    $code   the untis id of the unit being iterated
	 *
	 * @return void modifies the model, setting the id property of the resource
	 */
	public static function setID(Schedule $model, string $code)
	{
		$unit  = $model->units->$code;
		$table = new Tables\Units();

		if ($table->load(['organizationID' => $unit->organizationID, 'termID' => $unit->termID, 'code' => $code]))
		{
			foreach ($unit as $key => $value)
			{
				if (property_exists($table, $key) and $table->$key != $value)
				{
					$table->set($key, $value);
				}
			}

			$table->store();

		}
		else
		{
			$table->save($unit);
		}

		$unit->id = $table->id;
	}

	/**
	 * Checks whether nodes have the expected structure and required information
	 *
	 * @param   Schedule  $model  the model for the schedule being validated
	 *
	 * @return void modifies &$model
	 */
	public static function setWarnings(Schedule $model)
	{
		if (!empty($model->warnings['MID']))
		{
			$warningCount = $model->warnings['MID'];
			unset($model->warnings['MID']);
			$model->warnings[] = sprintf(Languages::_('ORGANIZER_METHOD_ID_WARNING'), $warningCount);
		}

		if (!empty($model->warnings['IMR']))
		{
			self::createMissingRoomsMessages($model);
		}

		if (!empty($model->warnings['IIR']))
		{
			self::createInvalidRoomsMessages($model);
		}
	}

	/**
	 * @inheritDoc
	 */
	public static function validate(Schedule $model, SimpleXMLElement $node)
	{
		// Unit has no instances and should not have been exported
		if (empty($node->times->count()))
		{
			return;
		}

		$effBeginDT  = isset($node->begindate) ?
			strtotime(trim((string) $node->begindate)) : strtotime(trim((string) $node->effectivebegindate));
		$termBeginDT = strtotime($model->term->startDate);
		$effEndDT    = isset($node->enddate) ?
			strtotime(trim((string) $node->enddate)) : strtotime(trim((string) $node->effectiveenddate));
		$termEndDT   = strtotime($model->term->endDate);

		// Unit starts after term ends or ends before term begins
		if ($effBeginDT > $termEndDT or $effEndDT < $termBeginDT)
		{
			return;
		}

		// Unit overlaps beginning of term => use term start
		$effBeginDT = $effBeginDT < $termBeginDT ? $termBeginDT : $effBeginDT;

		// Unit overlaps end of term => use term end
		$effEndDT = $termEndDT < $effEndDT ? $termEndDT : $effEndDT;

		// Reset variables passed through the object
		$rawUntisID = str_replace("LS_", '', trim((string) $node[0]['id']));
		$untisID    = substr($rawUntisID, 0, strlen($rawUntisID) - 2);

		$gridID = null;
		if (!$gridName = trim((string) $node->timegrid))
		{
			$model->errors[] = sprintf(Languages::_('ORGANIZER_UNIT_GRID_MISSING'), $untisID);
		}
		elseif (!$gridID = Grids::getID($gridName))
		{
			$model->errors[] = sprintf(Languages::_('ORGANIZER_UNIT_GRID_INVALID'), $untisID, $gridName);
		}

		$comment = trim((string) $node->text);

		if (empty($model->units->$untisID))
		{
			$unit                 = new stdClass();
			$unit->organizationID = $model->organizationID;
			$unit->termID         = $model->termID;
			$unit->code           = $untisID;
			$unit->gridID         = $gridID;
			$unit->gridName       = $gridName;
			$unit->roleID         = self::getRoleID(trim((string) $node->text1));
			$unit->startDate      = date('Y-m-d', $effBeginDT);
			$unit->startDT        = $effBeginDT;
			$unit->endDate        = date('Y-m-d', $effEndDT);
			$unit->endDT          = $effEndDT;
			$unit->comment        = (empty($comment) or $comment == '.') ? '' : $comment;

			// Backwards compatibility
			$unit->subjects = new stdClass();
		}
		else
		{
			$unit         = $model->units->$untisID;
			$unit->roleID = self::getRoleID(trim((string) $node->text1));
		}

		$model->units->$untisID = $unit;

		$valid = count($model->errors) === 0;
		if ($valid)
		{
			self::setID($model, $untisID);
		}

		$valid = (self::validateDates($model, $untisID) and $valid);
		$valid = (self::validateEvent($model, $node, $untisID) and $valid);
		$valid = (self::validateGroups($model, $node, $untisID) and $valid);
		$valid = (self::validatePerson($model, $node, $untisID) and $valid);
		$valid = (self::validateMethod($model, $node, $untisID) and $valid);

		// Adjusted dates are used because effective dts are not always accurate for the time frame
		$filteredOccurrences = self::getFilteredOccurrences($model, $node, $untisID);

		// Cannot produce blocking errors
		Instances::validateCollection($model, $node->times, $untisID, $filteredOccurrences, $valid);
	}

	/**
	 * Checks for the validity and consistency of date values
	 *
	 * @param   Schedule  $model    the model for the schedule being validated
	 * @param   string    $untisID  the untis id of the unit being iterated
	 *
	 * @return bool  true if dates are valid, otherwise false
	 */
	private static function validateDates(Schedule $model, string $untisID): bool
	{
		$unit  = $model->units->$untisID;
		$valid = true;

		if (empty($unit->startDT))
		{
			$model->errors[] = sprintf(Languages::_('ORGANIZER_UNIT_START_DATE_MISSING'), $untisID);

			$valid = false;
		}

		$syStartTime = strtotime($model->schoolYear->startDate);
		$syEndTime   = strtotime($model->schoolYear->endDate);

		if ($unit->startDT < $syStartTime or $unit->startDT > $syEndTime)
		{
			$model->errors[] = sprintf(
				Languages::_('ORGANIZER_UNIT_START_DATE_INVALID'),
				$untisID,
				$unit->startDate
			);

			$valid = false;
		}

		if (empty($unit->endDT))
		{
			$model->errors[] = sprintf(Languages::_('ORGANIZER_UNIT_END_DATE_MISSING'), $untisID);

			$valid = false;
		}

		$validEndDate = ($unit->endDT >= $syStartTime and $unit->endDT <= $syEndTime);
		if (!$validEndDate)
		{
			$model->errors[] = sprintf(
				Languages::_('ORGANIZER_UNIT_END_DATE_INVALID'),
				$untisID,
				$unit->endDate
			);

			$valid = false;
		}

		// Checks if start date is before end date
		if ($unit->endDT < $unit->startDT)
		{
			$model->errors[] = sprintf(
				Languages::_('ORGANIZER_UNIT_DATES_INCONSISTENT'),
				$untisID,
				$unit->startDate,
				$unit->endDate
			);

			$valid = false;
		}

		return $valid;
	}

	/**
	 * Validates the subjectID and builds dependant structural elements
	 *
	 * @param   Schedule          $model    the model for the schedule being validated
	 * @param   SimpleXMLElement  $node     the node being validated
	 * @param   string            $untisID  the untis id of the unit being iterated
	 *
	 * @return bool  true on success, otherwise bool false
	 */
	private static function validateEvent(Schedule $model, SimpleXMLElement $node, string $untisID): bool
	{
		$eventCode = str_replace('SU_', '', trim((string) $node->lesson_subject[0]['id']));

		if (empty($eventCode))
		{
			$model->errors[] = sprintf(Languages::_('ORGANIZER_UNIT_EVENT_MISSING'), $untisID);

			return false;
		}

		if (empty($model->events->$eventCode))
		{
			$model->errors[] = sprintf(Languages::_('ORGANIZER_UNIT_EVENT_INVALID'), $untisID, $eventCode);

			return false;
		}

		$eventID = $model->events->$eventCode->id;

		$model->units->$untisID->eventID = $eventID;

		// Backwards compatibility from here on.
		if (empty($model->units->$untisID->subjects))
		{
			$model->units->$untisID->subjects = new stdClass();
		}

		if (empty($model->units->$untisID->subjects->$eventID))
		{
			$entry            = new stdClass();
			$entry->subjectNo = $model->events->$eventCode->subjectNo;
			$entry->pools     = new stdClass();
			$entry->teachers  = new stdClass();

			$model->units->$untisID->subjects->$eventID = $entry;
		}

		return true;
	}

	/**
	 * Validates the groups attribute and sets corresponding schedule elements
	 *
	 * @param   Schedule          $model    the model for the schedule being validated
	 * @param   SimpleXMLElement  $node     the node being validated
	 * @param   string            $untisID  the untis id of the unit being iterated
	 *
	 * @return bool  true if valid, otherwise false
	 */
	private static function validateGroups(Schedule $model, SimpleXMLElement $node, string $untisID): bool
	{
		$rawUntisIDs = str_replace('CL_', '', (string) $node->lesson_classes[0]['id']);

		if (empty($rawUntisIDs))
		{
			$model->errors[] = sprintf(Languages::_('ORGANIZER_UNIT_GROUPS_MISSING'), $untisID);

			return false;
		}

		$unit = $model->units->$untisID;

		if (empty($unit->eventID))
		{
			// The error would have already been put in place by event validation.
			return false;
		}

		$eventID      = $unit->eventID;
		$unit->groups = [];
		$groupCodes   = explode(" ", $rawUntisIDs);

		foreach ($groupCodes as $groupCode)
		{
			if (empty($model->groups->$groupCode))
			{
				$model->warnings[] = sprintf(Languages::_('ORGANIZER_UNIT_GROUP_INVALID'), $untisID, $groupCode);

				continue;
			}

			$groupID        = $model->groups->$groupCode->id;
			$unit->groups[] = $groupID;

			// Backwards compatibility.
			$unit->subjects->$eventID->pools->$groupID = '';
		}

		return count($unit->groups) ? true : false;
	}

	/**
	 * Validates the description
	 *
	 * @param   Schedule          $model    the model for the schedule being validated
	 * @param   SimpleXMLElement  $node     the node being validated
	 * @param   string            $untisID  the untis id of the unit being iterated
	 *
	 * @return bool true if valid, otherwise false
	 */
	private static function validateMethod(Schedule $model, SimpleXMLElement $node, string $untisID): bool
	{
		$methodID = trim((string) $node->lesson_description);
		if (empty($methodID))
		{
			$model->warnings['MID'] = empty($model->warnings['MID']) ? 1 : $model->warnings['MID'] + 1;

			return true;
		}

		if (empty($model->methods->$methodID))
		{
			$model->errors[] = sprintf(Languages::_('ORGANIZER_UNIT_METHOD_INVALID'), $untisID, $methodID);

			return false;
		}

		$model->units->$untisID->methodID = $model->methods->$methodID;

		return true;
	}

	/**
	 * Validates the unit's teacher attribute and sets corresponding schedule elements
	 *
	 * @param   Schedule          $model    the model for the schedule being validated
	 * @param   SimpleXMLElement  $node     the node being validated
	 * @param   string            $untisID  the untis id of the unit being iterated
	 *
	 * @return bool  true if valid, otherwise false
	 */
	private static function validatePerson(Schedule $model, SimpleXMLElement $node, string $untisID): bool
	{
		$personCode = str_replace('TR_', '', trim((string) $node->lesson_teacher[0]['id']));

		if (empty($personCode))
		{
			$model->errors[] = sprintf(Languages::_('ORGANIZER_UNIT_PERSON_MISSING'), $untisID);

			return false;
		}

		if (empty($model->persons->$personCode))
		{
			$model->errors[] = sprintf(Languages::_('ORGANIZER_UNIT_PERSON_INVALID'), $untisID, $personCode);

			return false;
		}

		$personID                         = $model->persons->$personCode->id;
		$model->units->$untisID->personID = $personID;

		// Backwards compatibility
		$unit = $model->units->$untisID;

		// Error message already added by the event validation.
		if (empty($unit->eventID))
		{
			return false;
		}

		$unit->subjects->{$unit->eventID}->teachers->$personID = '';

		return true;
	}
}
