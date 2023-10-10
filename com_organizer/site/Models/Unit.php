<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers;
use THM\Organizer\Tables;

/**
 * Class which manages stored unit data.
 */
class Unit extends BaseModel
{
    /**
     * Creates a course based on the information associated with the given unit.
     * @return int the id of the newly created course
     */
    public function addCourse()
    {
        $unit = new Tables\Units();
        if (!$unitID = Input::getSelectedID() or !$unit->load($unitID)) {
            return false;
        }

        if ($unit->courseID) {
            return $unit->courseID;
        }

        $authorized = Helpers\Can::scheduleTheseOrganizations();
        if (!in_array($unit->organizationID, $authorized)) {
            Application::error(403);
        }

        $event  = new Tables\Events();
        $course = new Tables\Courses();

        foreach (Helpers\Units::getEventIDs($unitID) as $eventID) {
            $event->load($eventID);

            if ($course->name_de === null) {
                $course->name_de = $event->name_de;
            } elseif (!strpos($course->name_de, $event->name_de)) {
                $course->name_de .= ' / ' . $event->name_de;
            }

            if ($course->name_en === null) {
                $course->name_en = $event->name_en;
            } elseif (!strpos($course->name_en, $event->name_en)) {
                $course->name_en .= ' / ' . $event->name_en;
            }

            if ($course->deadline === null or $event->deadline < $course->deadline) {
                $course->deadline = $event->deadline;
            }

            if ($course->fee === null or $event->fee < $course->fee) {
                $course->fee = $event->fee;
            }

            if ($course->maxParticipants === null or $event->maxParticipants < $course->maxParticipants) {
                $course->maxParticipants = $event->maxParticipants;
            }

            if ($course->registrationType === null or $event->registrationType < $course->registrationType) {
                $course->registrationType = $event->registrationType;
            }
        }

        $course->campusID = Helpers\Units::getCampusID($unit->id, $event->campusID);
        $course->termID   = $unit->termID;

        if (!$course->store()) {
            return 0;
        }

        $unit->courseID = $course->id;
        $unit->store();

        return $course->id;
    }

    /**
     * @inheritDoc
     */
    public function getTable($name = '', $prefix = '', $options = [])
    {
        return new Tables\Units();
    }
}
