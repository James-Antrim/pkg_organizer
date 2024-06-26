<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers\Validators;

use SimpleXMLElement;
use stdClass;
use THM\Organizer\Adapters\{Database as DB, Text};
use THM\Organizer\Controllers\Schedule;
use THM\Organizer\Tables\Units as Table;

/**
 * Provides functions for XML unit validation and persistence.
 */
class Units implements UntisXMLValidator
{
    /**
     * Determines how the missing room attribute will be handled
     *
     * @param   Schedule  $controller  the model for the schedule being validated
     *
     * @return void adds a message to the model warnings array
     */
    private static function addIRMessages(Schedule $controller): void
    {
        foreach ($controller->warnings['IIR'] as $untisID => $invalidRooms) {
            asort($invalidRooms);
            $invalidRooms = implode(', ', $invalidRooms);
            $pos          = strrpos(', ', $invalidRooms);
            if ($pos !== false) {
                $and          = Text::_('AND');
                $invalidRooms = substr_replace($invalidRooms, " $and ", $pos, strlen($invalidRooms));
            }

            $controller->warnings[] = Text::sprintf('UNIT_ROOM_INCOMPLETE', $untisID, $invalidRooms
            );
        }
        unset($controller->warnings['IIR']);
    }

    /**
     * Determines how the missing room attribute will be handled
     *
     * @param   Schedule  $controller  the model for the schedule being validated
     *
     * @return void adds a message to the model warnings array
     */
    private static function addMRMessages(Schedule $controller): void
    {
        foreach ($controller->warnings['IMR'] as $untisID => $dows) {
            foreach ($dows as $dow => $periods) {
                foreach ($periods as $periodNo => $missingDates) {
                    if (count($missingDates) > 2) {
                        $controller->warnings[] = Text::sprintf('UNIT_ROOMS_MISSING', $untisID, $dow, $periodNo);
                        continue;
                    }

                    $dates = implode(', ', $missingDates);
                    $pos   = strrpos(', ', $dates);
                    if ($pos !== false) {
                        $and   = Text::_('AND');
                        $dates = substr_replace($dates, " $and ", $pos, strlen($dates));
                    }

                    $controller->warnings[] = Text::sprintf('UNIT_ROOMS_MISSING', $untisID, $dates, $periodNo);
                }

            }
        }
        unset($controller->warnings['IMR']);
    }

    /**
     * Adjusts the temporal template ('occurrence' attribute) to the unit's actual dates.
     *
     * @param   Schedule          $controller  the model for the schedule being validated
     * @param   SimpleXMLElement  $node        the node being validated
     * @param   string            $untisID     the untis id of the unit being iterated
     *
     * @return string[]   the occurrences string modeled by an array
     */
    private static function filterOccurrences(Schedule $controller, SimpleXMLElement $node, string $untisID): array
    {
        $rawOccurrences = trim((string) $node->occurence);
        $unit           = $controller->units->$untisID;

        // Increases the end value one day (Untis uses inclusive dates)
        $end = strtotime('+1 day', $unit->endDT);

        // 86400 is the number of seconds in a day 24 * 60 * 60
        $offset = floor(($unit->startDT - strtotime($controller->schoolYear->startDate)) / 86400);
        $length = floor(($end - $unit->startDT) / 86400);

        $filteredOccurrences = substr($rawOccurrences, $offset, $length);

        // Change occurrences from a string to an array of the appropriate length for iteration
        return empty($filteredOccurrences) ? [] : str_split($filteredOccurrences);
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

        if (empty($role) or !preg_match('/^[a-zA-Z]+$/', $role)) {
            return 1;
        }

        $query = DB::getQuery();
        $query->select(DB::qn('id'))
            ->from(DB::qn('#__organizer_roles'))
            ->where(DB::qc('code', strtoupper($role), '=', true));
        DB::setQuery($query);

        return DB::loadInt(1);
    }

    /**
     * Retrieves the resource id using the Untis ID. Creates the resource id if unavailable.
     *
     * @param   Schedule  $controller  the model for the schedule being validated
     * @param   string    $code        the untis id of the unit being iterated
     *
     * @return void modifies the model, setting the id property of the resource
     */
    public static function setID(Schedule $controller, string $code): void
    {
        $unit  = $controller->units->$code;
        $table = new Table();

        if ($table->load(['organizationID' => $unit->organizationID, 'termID' => $unit->termID, 'code' => $code])) {
            $updated = false;
            foreach ($unit as $key => $value) {
                if (property_exists($table, $key) and $table->$key != $value) {
                    $updated = true;
                    $table->set($key, $value);
                }
            }

            if ($updated) {
                $table->modified = $controller->modified;
                $table->store();
            }
        }
        else {
            $table->modified = $controller->modified;
            $table->save($unit);
        }

        $unit->id = $table->id;
    }

    /**
     * Checks whether nodes have the expected structure and required information
     *
     * @param   Schedule  $controller  the model for the schedule being validated
     *
     * @return void modifies &$model
     */
    public static function setWarnings(Schedule $controller): void
    {
        if (!empty($controller->warnings['MID'])) {
            $warningCount = $controller->warnings['MID'];
            unset($controller->warnings['MID']);
            $controller->warnings[] = Text::sprintf('METHOD_ID_WARNING', $warningCount);
        }

        if (!empty($controller->warnings['IMR'])) {
            self::addMRMessages($controller);
        }

        if (!empty($controller->warnings['IIR'])) {
            self::addIRMessages($controller);
        }
    }

    /**
     * @inheritDoc
     */
    public static function validate(Schedule $controller, SimpleXMLElement $node): void
    {
        // Unit has no instances and should not have been exported
        if (empty($node->times->count())) {
            return;
        }

        $effBeginDT  = isset($node->begindate) ?
            strtotime(trim((string) $node->begindate)) : strtotime(trim((string) $node->effectivebegindate));
        $termBeginDT = strtotime($controller->term->startDate);
        $effEndDT    = isset($node->enddate) ?
            strtotime(trim((string) $node->enddate)) : strtotime(trim((string) $node->effectiveenddate));
        $termEndDT   = strtotime($controller->term->endDate);

        // Unit starts after term ends or ends before term begins
        if ($effBeginDT > $termEndDT or $effEndDT < $termBeginDT) {
            return;
        }

        // Unit overlaps beginning of term => use term start
        $effBeginDT = max($effBeginDT, $termBeginDT);

        // Unit overlaps end of term => use term end
        $effEndDT = min($termEndDT, $effEndDT);

        // Reset variables passed through the object
        $rawUntisID = str_replace("LS_", '', trim((string) $node[0]['id']));
        $untisID    = substr($rawUntisID, 0, strlen($rawUntisID) - 2);

        $gridID = null;
        if (!$gridName = trim((string) $node->timegrid)) {
            $controller->errors[] = Text::sprintf('UNIT_GRID_MISSING', $untisID);
        }
        elseif (!$gridID = Grids::getID($gridName)) {
            $controller->errors[] = Text::sprintf('UNIT_GRID_INVALID', $untisID, $gridName);
        }

        if (empty($controller->units->$untisID)) {
            $comment              = trim((string) $node->text);
            $unit                 = new stdClass();
            $unit->organizationID = $controller->organizationID;
            $unit->termID         = $controller->termID;
            $unit->code           = $untisID;
            $unit->gridID         = $gridID;
            $unit->gridName       = $gridName;
            $unit->startDate      = date('Y-m-d', $effBeginDT);
            $unit->startDT        = $effBeginDT;
            $unit->endDate        = date('Y-m-d', $effEndDT);
            $unit->endDT          = $effEndDT;
            $unit->comment        = (empty($comment) or $comment === '.') ? '' : $comment;
            $unit->effStartDate   = '';
            $unit->effEndDate     = '';

            // Backwards compatibility
            $unit->subjects = new stdClass();
        }
        else {
            $unit = $controller->units->$untisID;
        }

        $iComment       = trim((string) $node->text2);
        $unit->iComment = !$iComment ? '' : $iComment;
        $unit->roleID   = self::getRoleID(trim((string) $node->text1));

        $controller->units->$untisID = $unit;

        $valid = count($controller->errors) === 0;
        if ($valid) {
            self::setID($controller, $untisID);
        }

        $valid = (self::validateDates($controller, $untisID) and $valid);
        $valid = (self::validateEvent($controller, $node, $untisID) and $valid);
        $valid = (self::validateGroups($controller, $node, $untisID) and $valid);
        $valid = (self::validatePerson($controller, $node, $untisID) and $valid);
        $valid = (self::validateMethod($controller, $node, $untisID) and $valid);

        // Adjusted dates are used because effective dts are not always accurate for the time frame
        $filteredOccurrences = self::filterOccurrences($controller, $node, $untisID);

        // Cannot produce blocking errors
        Instances::validateCollection($controller, $node->times, $untisID, $filteredOccurrences, $valid);
    }

    /**
     * Checks for the validity and consistency of date values
     *
     * @param   Schedule  $controller  the model for the schedule being validated
     * @param   string    $untisID     the untis id of the unit being iterated
     *
     * @return bool  true if dates are valid, otherwise false
     */
    private static function validateDates(Schedule $controller, string $untisID): bool
    {
        $unit  = $controller->units->$untisID;
        $valid = true;

        if (empty($unit->startDT)) {
            $controller->errors[] = Text::sprintf('UNIT_START_DATE_MISSING', $untisID);
            $valid                = false;
        }

        $syStartTime = strtotime($controller->schoolYear->startDate);
        $syEndTime   = strtotime($controller->schoolYear->endDate);

        if ($unit->startDT < $syStartTime or $unit->startDT > $syEndTime) {
            $controller->errors[] = Text::sprintf('UNIT_START_DATE_INVALID', $untisID, $unit->startDate);
            $valid                = false;
        }

        if (empty($unit->endDT)) {
            $controller->errors[] = Text::sprintf('UNIT_END_DATE_MISSING', $untisID);
            $valid                = false;
        }

        $validEndDate = ($unit->endDT >= $syStartTime and $unit->endDT <= $syEndTime);
        if (!$validEndDate) {
            $controller->errors[] = Text::sprintf('UNIT_END_DATE_INVALID', $untisID, $unit->endDate);
            $valid                = false;
        }

        // Checks if start date is before end date
        if ($unit->endDT < $unit->startDT) {
            $controller->errors[] = Text::sprintf('UNIT_DATES_INCONSISTENT', $untisID, $unit->startDate, $unit->endDate);
            $valid                = false;
        }

        return $valid;
    }

    /**
     * Validates the subjectID and builds dependant structural elements
     *
     * @param   Schedule          $controller  the model for the schedule being validated
     * @param   SimpleXMLElement  $node        the node being validated
     * @param   string            $untisID     the untis id of the unit being iterated
     *
     * @return bool  true on success, otherwise bool false
     */
    private static function validateEvent(Schedule $controller, SimpleXMLElement $node, string $untisID): bool
    {
        $eventCode = str_replace('SU_', '', trim((string) $node->lesson_subject[0]['id']));

        if (empty($eventCode)) {
            $controller->errors[] = Text::sprintf('UNIT_EVENT_MISSING', $untisID);

            return false;
        }

        if (empty($controller->events->$eventCode)) {
            $controller->errors[] = Text::sprintf('UNIT_EVENT_INVALID', $untisID, $eventCode);

            return false;
        }

        $eventID = $controller->events->$eventCode->id;

        $controller->units->$untisID->eventID = $eventID;

        // Backwards compatibility from here on.
        if (empty($controller->units->$untisID->subjects)) {
            $controller->units->$untisID->subjects = new stdClass();
        }

        if (empty($controller->units->$untisID->subjects->$eventID)) {
            $entry            = new stdClass();
            $entry->subjectNo = $controller->events->$eventCode->subjectNo;
            $entry->pools     = new stdClass();
            $entry->teachers  = new stdClass();

            $controller->units->$untisID->subjects->$eventID = $entry;
        }

        return true;
    }

    /**
     * Validates the groups attribute and sets corresponding schedule elements
     *
     * @param   Schedule          $controller  the model for the schedule being validated
     * @param   SimpleXMLElement  $node        the node being validated
     * @param   string            $untisID     the untis id of the unit being iterated
     *
     * @return bool  true if valid, otherwise false
     */
    private static function validateGroups(Schedule $controller, SimpleXMLElement $node, string $untisID): bool
    {
        $rawUntisIDs = str_replace('CL_', '', (string) $node->lesson_classes[0]['id']);

        if (empty($rawUntisIDs)) {
            $controller->errors[] = Text::sprintf('UNIT_GROUPS_MISSING', $untisID);

            return false;
        }

        $unit = $controller->units->$untisID;

        if (empty($unit->eventID)) {
            // The error would have already been put in place by event validation.
            return false;
        }

        $eventID      = $unit->eventID;
        $unit->groups = [];
        $groupCodes   = explode(" ", $rawUntisIDs);

        foreach ($groupCodes as $groupCode) {
            if (empty($controller->groups->$groupCode)) {
                $controller->warnings[] = Text::sprintf('UNIT_GROUP_INVALID', $untisID, $groupCode);

                continue;
            }

            $groupID        = $controller->groups->$groupCode->id;
            $unit->groups[] = $groupID;

            // Backwards compatibility.
            $unit->subjects->$eventID->pools->$groupID = '';
        }

        return (bool) count($unit->groups);
    }

    /**
     * Validates the description
     *
     * @param   Schedule          $controller  the model for the schedule being validated
     * @param   SimpleXMLElement  $node        the node being validated
     * @param   string            $untisID     the untis id of the unit being iterated
     *
     * @return bool true if valid, otherwise false
     */
    private static function validateMethod(Schedule $controller, SimpleXMLElement $node, string $untisID): bool
    {
        $methodID = trim((string) $node->lesson_description);
        if (empty($methodID)) {
            $controller->warnings['MID'] = empty($controller->warnings['MID']) ? 1 : $controller->warnings['MID'] + 1;

            return true;
        }

        if (empty($controller->tMethods->$methodID)) {
            $controller->errors[] = Text::sprintf('UNIT_METHOD_INVALID', $untisID, $methodID);

            return false;
        }

        $controller->units->$untisID->methodID = $controller->tMethods->$methodID;

        return true;
    }

    /**
     * Validates the unit's teacher attribute and sets corresponding schedule elements
     *
     * @param   Schedule          $controller  the model for the schedule being validated
     * @param   SimpleXMLElement  $node        the node being validated
     * @param   string            $untisID     the untis id of the unit being iterated
     *
     * @return bool  true if valid, otherwise false
     */
    private static function validatePerson(Schedule $controller, SimpleXMLElement $node, string $untisID): bool
    {
        $personCode = str_replace('TR_', '', trim((string) $node->lesson_teacher[0]['id']));

        if (empty($personCode)) {
            $controller->errors[] = Text::sprintf('UNIT_PERSON_MISSING', $untisID);

            return false;
        }

        if (empty($controller->persons->$personCode)) {
            $controller->errors[] = Text::sprintf('UNIT_PERSON_INVALID', $untisID, $personCode);

            return false;
        }

        $personID                              = $controller->persons->$personCode->id;
        $controller->units->$untisID->personID = $personID;

        // Backwards compatibility
        $unit = $controller->units->$untisID;

        // Error message already added by the event validation.
        if (empty($unit->eventID)) {
            return false;
        }

        $unit->subjects->{$unit->eventID}->teachers->$personID = '';

        return true;
    }

    /**
     * Updates the start and end dates of units after processing of the instances.
     *
     * @param   array  $units  the processed units
     *
     * @return void modifies database entries
     */
    public static function updateDates(array $units): void
    {
        foreach ($units as $unit) {
            if (!$unit->id or !$unit->effEndDate or !$unit->effStartDate) {
                continue;
            }

            $table = new Table();

            if (!$table->load($unit->id)) {
                continue;
            }

            $table->endDate   = $unit->effEndDate;
            $table->startDate = $unit->effStartDate;
            $table->store();
        }
    }
}
