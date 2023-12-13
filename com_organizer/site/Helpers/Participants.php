<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

use THM\Organizer\Adapters\Database;
use THM\Organizer\Adapters\User;
use THM\Organizer\Tables;

/**
 * Provides general functions for participant access checks, data retrieval and display.
 */
class Participants extends ResourceHelper
{
    /**
     * Determines whether the necessary participant properties have been set to register for a course.
     *
     * @param   int  $participantID  the id of the participant
     *
     * @return bool true if the necessary participant information has been set, otherwise false
     */
    public static function canRegister(int $participantID = 0): bool
    {
        $participantID = $participantID ?: User::id();
        $table         = new Tables\Participants();
        if ($table->load($participantID)) {
            $valid = (bool) $table->address;
            $valid = ($valid and $table->city);
            $valid = ($valid and $table->forename);
            $valid = ($valid and $table->programID);
            $valid = ($valid and $table->surname);

            return ($valid and $table->zipCode);
        }

        return false;
    }

    /**
     * Checks whether a participant entry already exists for the current user.
     *
     * @param   int  $participantID  the id of the potential participant to check
     *
     * @return bool true if the user is already associated with a participant, otherwise false
     */
    public static function exists(int $participantID = 0): bool
    {
        $participantID = $participantID ?: User::id();
        $participants  = new Tables\Participants();

        return $participants->load($participantID);
    }

    /**
     * Retrieves the ids of the courses with which the participant is associated.
     *
     * @param   int  $participantID  the id of the participant
     *
     * @return int[] the associated course ids if existent, otherwise empty
     */
    public static function getCourseIDs(int $participantID): array
    {
        $query = Database::getQuery();
        $query->select('courseID')
            ->from('#__organizer_course_participants')
            ->where("participantID = $participantID");
        Database::setQuery($query);

        return Database::loadIntColumn();
    }
}
