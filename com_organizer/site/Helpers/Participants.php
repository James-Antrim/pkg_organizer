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

use Joomla\Database\ParameterType;
use THM\Organizer\Adapters\{Database as DB, User};
use THM\Organizer\Tables;

/**
 * Provides general functions for participant access checks, data retrieval and display.
 */
class Participants extends ResourceHelper
{
    /**
     * Determines whether the necessary participant properties have been set to register for a course.
     *
     * @param int $participantID the id of the participant
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
     * Filters field data for actual letters and accepted special characters.
     *
     * @param string $value the raw value
     *
     * @return string
     */
    public static function cleanAlpha(string $value): string
    {
        return preg_replace('/[^A-ZÀ-ÖØ-Þa-zß-ÿ\p{N}_.\-\']/', ' ', $value);
    }

    /**
     * Filters field data for actual letters, accepted special characters and numbers.
     *
     * @param string $value the raw value
     *
     * @return string
     */
    public static function cleanAlphaNum(string $value): string
    {
        return preg_replace('/[^A-ZÀ-ÖØ-Þa-zß-ÿ\d\p{N}_.\-\']/', ' ', $value);
    }

    /**
     * Checks whether a participant entry already exists for the current user.
     *
     * @param int $participantID the id of the potential participant to check
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
     * @param int $participantID the id of the participant
     *
     * @return int[] the associated course ids if existent, otherwise empty
     */
    public static function getCourseIDs(int $participantID): array
    {
        $query = DB::query();
        $query->select('courseID')
            ->from('#__organizer_course_participants')
            ->where("participantID = $participantID");
        DB::set($query);

        return DB::integers();
    }

    /**
     * Resolves a username attribute into forename and surname attributes.
     *
     * @param int $userID the id of the user whose full name should be resolved
     *
     * @return string[] the first and last names of the user
     */
    private static function parseNames(int $userID = 0): array
    {
        $user = User::instance($userID);

        $sanitized  = self::trim(self::cleanAlpha($user->name));
        $fragments  = array_filter(explode(' ', $sanitized));
        $surname    = array_pop($fragments);
        $supplement = '';

        // The next element is a supplementary preposition.
        while (preg_match('/^[a-zß-ÿ]+$/', end($fragments))) {
            $supplement = array_pop($fragments);
            $surname    = "$supplement $surname";
        }

        // These supplements indicate the existence of a further surname fragment.
        if (in_array($supplement, ['zu', 'zum'])) {
            $add     = array_pop($fragments);
            $surname = "$add $surname";

            while (preg_match('/^[a-zß-ÿ]+$/', end($fragments))) {
                $supplement = array_pop($fragments);
                $surname    = "$supplement $surname";
            }
        }

        // Everything left is likely a forename
        return ['forename' => implode(" ", $fragments), 'surname' => $surname];
    }

    /**
     * Adds an organizer participant based on the information in the users table.
     *
     * @param int  $participantID the id of the participant/user entries
     * @param bool $force         forces update of the columns derived from information in the user table
     *
     * @return void
     */
    public static function supplement(int $participantID, bool $force = false): void
    {
        if ($exists = Participants::exists($participantID) and !$force) {
            return;
        }

        $forename = DB::qn('forename');
        $id       = DB::qn('id');
        $names    = self::parseNames($participantID);
        $query    = DB::query();
        $surname  = DB::qn('surname');
        $table    = DB::qn('#__organizer_participants');

        if (!$exists) {
            $query->insert($table)->columns([$id, $forename, $surname])->values(':id, :forename, :surname');
        }
        else {
            $query->update($table)->set("$forename = :forename")->set("$surname = :surname")->where("$id = :id");
        }

        $query->bind(':forename', $names['forename'])
            ->bind(':id', $participantID, ParameterType::INTEGER)
            ->bind(':surname', $names['surname']);

        DB::set($query);
        DB::execute();
    }

    /**
     * Removes excess spaces from a form value.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function trim(string $value): string
    {
        // Replace ideographic space
        $value = str_replace(chr(0xE3) . chr(0x80) . chr(0x80), ' ', $value);
        // Replace no-break space
        $value = str_replace(chr(0xC2) . chr(0xA0), ' ', $value);
        // Remove leading & trailing spaces
        $value = trim($value);
        // Remove surfeit spaces
        return preg_replace('/ +/', ' ', $value);
    }
}
