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
use THM\Organizer\Adapters\Database as DB;
use THM\Organizer\Tables;

/**
 * Provides general functions for course access checks, data retrieval and display.
 */
class CourseParticipants extends ResourceHelper
{

    public const ACCEPTED = 1, UNREGISTERED = null, WAITLIST = 0;
    public const ATTENDED = 1, UNATTENDED = 0;
    public const PAID = 1, UNPAID = 0;

    public const ATTENDANCE_STATES = [
        self::ATTENDED   => [
            'class'  => 'publish',
            'column' => 'attended',
            'task'   => 'denyAttendance',
            'tip'    => 'CLICK_TO_DENY_ATTENDANCE'
        ],
        self::UNATTENDED => [
            'class'  => 'unpublish',
            'column' => 'attended',
            'task'   => 'confirmAttendance',
            'tip'    => 'CLICK_TO_CONFIRM_ATTENDANCE'
        ]
    ];

    public const PAYMENT_STATES = [
        self::PAID   => [
            'class'  => 'publish',
            'column' => 'paid',
            'task'   => 'denyPayment',
            'tip'    => 'CLICK_TO_DENY_PAYMENT'
        ],
        self::UNPAID => [
            'class'  => 'unpublish',
            'column' => 'paid',
            'task'   => 'confirmPayment',
            'tip'    => 'CLICK_TO_CONFIRM_PAYMENT'
        ]
    ];

    public const REGISTRATION_STATES = [
        self::ACCEPTED => [
            'class'  => 'publish',
            'column' => 'status',
            'task'   => 'waitlist',
            'tip'    => 'CLICK_TO_WAITLIST'
        ],
        self::WAITLIST => [
            'class'  => 'unpublish',
            'column' => 'status',
            'task'   => 'accept',
            'tip'    => 'CLICK_TO_ACCEPT'
        ]
    ];

    /**
     * Determines whether the participant has paid for the course. Courses without a fee return true regardless of the
     * course participants table field contents.
     *
     * @param   int  $courseID       the course id
     * @param   int  $participantID  the participant id
     *
     * @return  bool
     */
    public static function paid(int $courseID, int $participantID): bool
    {
        $course = new Tables\Courses();

        if (!$course->load($courseID)) {
            return false;
        }
        elseif (empty($course->fee)) {
            return true;
        }

        $courseParticipant = new Tables\CourseParticipants();

        if (!$courseParticipant->load(['courseID' => $courseID, 'participantID' => $participantID])) {
            return false;
        }

        return $courseParticipant->paid;
    }

    /**
     * Retrieves the participant's state for the given course
     *
     * @param   int  $courseID       the course id
     * @param   int  $participantID  the id of the participant
     * @param   int  $eventID        the id of the specific course event
     *
     * @return  int|null
     */
    public static function state(int $courseID, int $participantID, int $eventID = 0): ?int
    {
        $query = DB::getQuery();
        $query->select(DB::qn('status'))
            ->from(DB::qn('#__organizer_course_participants', 'cp'))
            ->where(DB::qn('cp.courseID') . ' = :courseID')->bind(':courseID', $courseID, ParameterType::INTEGER)
            ->where(DB::qn('cp.participantID') . ' = :cParticipantID')
            ->bind(':cParticipantID', $participantID, ParameterType::INTEGER);

        if ($eventID) {
            $query->innerJoin(DB::qn('#__organizer_units', 'u'), DB::qc('u.courseID', 'cp.courseID'))
                ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.unitID', 'u.id'))
                ->innerJoin(DB::qn('#__organizer_instance_participants', 'ip'), DB::qc('ip.instanceID', 'i.id'))
                ->where(DB::qn('i.eventID') . ' = :eventID')->bind(':eventID', $eventID, ParameterType::INTEGER)
                ->where(DB::qn('ip.participantID') . ' = :iParticipantID')
                ->bind(':iParticipantID', $participantID, ParameterType::INTEGER);
        }

        DB::setQuery($query);
        return DB::loadResult();
    }

    /**
     * Checks whether all the necessary participant information has been entered.
     *
     * @param   int  $courseID       the id of the course to check against
     * @param   int  $participantID  the id of the participant to validate
     *
     * @return bool
     */
    public static function validProfile(int $courseID, int $participantID): bool
    {
        $participant = new Tables\Participants();
        if (empty($participantID) or !$participant->load($participantID)) {
            return false;
        }

        if (Courses::preparatory($courseID)) {
            $requiredProperties = ['address', 'city', 'forename', 'programID', 'surname', 'zipCode'];
        } // Resolve any other contexts here later.
        else {
            $requiredProperties = [];
        }

        foreach ($requiredProperties as $property) {
            if (empty($participant->$property)) {
                return false;
            }
        }

        return true;
    }
}
