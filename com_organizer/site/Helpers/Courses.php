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
use THM\Organizer\Adapters\{Application, Database as DB, User};
use THM\Organizer\Tables\Courses as Table;

/**
 * Provides general functions for course access checks, data retrieval and display.
 */
class Courses extends ResourceHelper
{
    /**
     * Check if the user is a course coordinator.
     *
     * @return int[]
     */
    public static function coordinates(): array
    {
        // The actual authorization has already occurred
        if (!$eventIDs = Events::coordinates()) {
            return [];
        }

        $query = DB::getQuery();
        $query->select('DISTINCT ' . DB::qn('c.id'))
            ->from(DB::qn('#__organizer_courses', 'c'))
            ->innerJoin(DB::qn('#__organizer_units', 'u'), DB::qc('u.courseID', 'c.id'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.unitID', 'u.id'))
            ->innerJoin(DB::qn('#__organizer_events', 'e'), DB::qc('e.id', 'ec.eventID'))
            ->whereIn(DB::qn('e.id'), $eventIDs);

        DB::setQuery($query);

        return DB::loadIntColumn();
    }

    /**
     * Retrieves the campus id associated with the course.
     *
     * @param   int  $courseID  the id of the course
     *
     * @return int  the id of the associated campus
     */
    public static function campusID(int $courseID): int
    {
        $course = new Table();

        return $course->load($courseID) ? (int) $course->campusID : 0;
    }

    /**
     * Gets the course start and end dates.
     *
     * @param   int  $courseID  id of course to be loaded
     *
     * @return string[]  the start and end date for the given course
     */
    public static function dates(int $courseID = 0): array
    {
        if (!$courseID) {
            return [];
        }

        $endDate   = DB::qn('endDate');
        $endDate   = "MAX($endDate) AS $endDate";
        $startDate = DB::qn('startDate');
        $startDate = "DISTINCT MIN($startDate) AS $startDate";

        $query = DB::getQuery();
        $query->select([$startDate, $endDate])
            ->from(DB::qn('#__organizer_units'))
            ->where(DB::qn('courseID') . ' = :courseID')
            ->bind(':courseID', $courseID, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::loadAssoc();
    }

    /**
     * Creates a display of formatted dates for a course
     *
     * @param   int  $courseID  the id of the course to be loaded
     *
     * @return string the dates to display
     */
    public static function displayDate(int $courseID): string
    {
        if ($dates = self::dates($courseID)) {
            return Dates::getDisplay($dates['startDate'], $dates ['endDate']);
        }

        return '';
    }

    /**
     * Retrieves events associated with the given course.
     *
     * @param   int  $courseID  the id of the course
     *
     * @return array[] the events associated with the course
     */
    public static function events(int $courseID): array
    {
        $tag      = Application::getTag();
        $aliased  = DB::qn(
            [
                "contact_$tag",
                "content_$tag",
                "courseContact_$tag",
                "e.description_$tag",
                "e.name_$tag",
                "organization_$tag",
                "pretests_$tag",
            ],
            [
                'contact',
                'content',
                'courseContact',
                'description',
                'name',
                'organization',
                'pretests',
            ]
        );
        $selected = ['DISTINCT ' . DB::qn('e.id'), DB::qn('preparatory')];

        $query = DB::getQuery();
        $query->select(array_merge($selected, $aliased))
            ->from(DB::qn('#__organizer_events', 'e'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.eventID', 'e.id'))
            ->innerJoin(DB::qn('#__organizer_units', 'u'), DB::qc('u.id', 'i.unitID'))
            ->where(DB::qn('u.courseID') . ' = :courseID')->bind(':courseID', $courseID, ParameterType::INTEGER)
            ->order(DB::qn('name'));
        DB::setQuery($query);

        if (!$events = DB::loadAssocList()) {
            return [];
        }

        foreach ($events as &$event) {
            $event['speakers'] = self::persons($courseID, $event['id'], [Roles::SPEAKER]);
            $event['teachers'] = self::persons($courseID, $event['id'], [Roles::TEACHER]);
            $event['tutors']   = self::persons($courseID, $event['id'], [Roles::TUTOR]);
        }

        return $events;
    }

    /**
     * Checks if the course is expired
     *
     * @param   int  $courseID  the id of the course
     *
     * @return bool true if the course is expired, otherwise false
     */
    public static function expired(int $courseID): bool
    {
        if ($dates = self::dates($courseID)) {
            return date('Y-m-d') > $dates['endDate'];
        }

        return true;
    }

    /**
     * Checks if the number of active participants is less than the number of max participants
     *
     * @param   int  $courseID  the id of the course
     *
     * @return bool true if the course is full, otherwise false
     */
    public static function full(int $courseID): bool
    {
        $table = new Table();
        if (!$table->load($courseID) or !$maxParticipants = $table->maxParticipants) {
            return false;
        }

        $accepted = CourseParticipants::ACCEPTED;
        $query    = DB::getQuery();
        $query->select('COUNT(*)')
            ->from(DB::qn('#__organizer_course_participants'))
            ->where(DB::qn('courseID') . ' = :courseID')->bind(':courseID', $courseID, ParameterType::INTEGER)
            ->where(DB::qn('status') . ' = :status')->bind(':status', $accepted, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::loadInt() >= $maxParticipants;
    }

    /**
     * Gets an array of participant IDs for a given course, optionally filtered by the participant's status
     *
     * @param   int  $courseID  the course id
     *
     * @return array[] list of participants in course
     */
    public static function groupedParticipation(int $courseID): array
    {
        if (empty($courseID)) {
            return [];
        }

        $tag      = Application::getTag();
        $aliased  = DB::qn(['d.abbreviation', "pr.name_$tag", 'pr.accredited'], ['degree', 'program', 'year']);
        $selected = [DB::qn('pr.id'), 'COUNT(*) AS ' . DB::qn('participants')];

        $query = DB::getQuery();
        $query->select(array_merge($selected, $aliased))
            ->from(DB::qn('#__organizer_programs', 'pr'))
            ->innerJoin(DB::qn('#__organizer_degrees', 'd'), DB::qc('d.id', 'pr.degreeID'))
            ->innerJoin(DB::qn('#__organizer_participants', 'pa'), DB::qc('pa.programID', 'pr.id'))
            ->innerJoin(DB::qn('#__organizer_course_participants', 'cp'), DB::qc('cp.participantID', 'pa.id'))
            ->where(DB::qn('courseID') . ' = :courseID')->bind(':courseID', $courseID, ParameterType::INTEGER)
            ->order([DB::qn("pr.name_$tag"), DB::qn('d.abbreviation'), DB::qn('pr.accredited') . ' DESC'])
            ->group("pr.id");
        DB::setQuery($query);

        if (!$programCounts = DB::loadAssocList()) {
            return $programCounts;
        }

        $results = [];

        foreach ($programCounts as $programCount) {
            $organizationIDs = Programs::organizationIDs($programCount['id']);
            foreach ($organizationIDs as $organizationID) {
                $organization = Organizations::getFullName($organizationID);

                if (empty($results[$organization])) {
                    $results[$organization] = [
                        'participants'      => $programCount['participants'],
                        $programCount['id'] => $programCount
                    ];
                }
                else {
                    $results[$organization]['participants']
                        = $results[$organization]['participants'] + $programCount['participants'];
                }

                $results[$organization][$programCount['id']] = $programCount;
            }
        }

        ksort($results);

        return $results;
    }

    /**
     * Check if user has a course responsibility.
     *
     * @param   int  $courseID  the optional id of the course
     * @param   int  $personID  the optional id of the person
     * @param   int  $roleID    the optional if of the person's role
     *
     * @return bool true if the user has a course responsibility, otherwise false
     */
    public static function hasResponsibility(int $courseID = 0, int $personID = 0, int $roleID = 0): bool
    {
        if (Can::administrate()) {
            return true;
        }

        if (!$personID = $personID ?: Persons::getIDByUserID(User::id())) {
            return false;
        }

        $query = DB::getQuery();
        $query->select('COUNT(*)')
            ->from(DB::qn('#__organizer_instance_persons', 'ip'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.id', 'ip.instanceID'))
            ->innerJoin(DB::qn('#__organizer_units', 'u'), DB::qc('u.id', 'i.unitID'))
            ->where(DB::qn('ip.personID') . ' = :personID')->bind(':personID', $personID, ParameterType::INTEGER);

        if ($courseID) {
            $query->where(DB::qn('u.courseID') . ' = :courseID')->bind(':courseID', $courseID, ParameterType::INTEGER);
        }

        if ($roleID) {
            $query->where(DB::qn('ip.roleID') . ' = :roleID')->bind(':roleID', $roleID, ParameterType::INTEGER);
        }

        DB::setQuery($query);

        return DB::loadBool();
    }

    /**
     * Gets instances associated with the given course.
     *
     * @param   int  $courseID  the id of the course
     *
     * @return int[] the instances which are a part of the course
     */
    public static function instanceIDs(int $courseID): array
    {
        $query = DB::getQuery();
        $query->select('DISTINCT ' . DB::qn('i.id'))
            ->from(DB::qn('#__organizer_instances', 'i'))
            ->innerJoin(DB::qn('#__organizer_units', 'u'), DB::qc('u.id', 'i.unitID'))
            ->where(DB::qn('u.courseID') . ' = :courseID')->bind(':courseID', $courseID, ParameterType::INTEGER)
            ->order(DB::qn('i.id'));
        DB::setQuery($query);

        return DB::loadIntColumn();
    }

    /**
     * Gets an array of participant IDs for a given course, optionally filtered by the participant's status
     *
     * @param   int  $courseID  the course id
     *
     * @return int [] the participant IDs
     */
    public static function participantIDs(int $courseID): array
    {
        if (empty($courseID)) {
            return [];
        }

        $participantID = DB::qn('participantID');

        $query = DB::getQuery();
        $query->select($participantID)
            ->from(DB::qn('#__organizer_course_participants'))
            ->where(DB::qn('courseID') . ' = :courseID')
            ->bind(':courseID', $courseID, ParameterType::INTEGER)
            ->order($participantID);

        DB::setQuery($query);

        return DB::loadIntColumn();
    }

    /**
     * Gets persons associated with the given course, optionally filtered by event and role.
     *
     * @param   int    $courseID  the id of the course
     * @param   int    $eventID   the id of the event
     * @param   array  $roleIDs   the id of the roles the persons should have
     *
     * @return string[] the persons matching the search criteria
     */
    public static function persons(int $courseID, int $eventID = 0, array $roleIDs = []): array
    {
        $query = DB::getQuery();
        $query->select('DISTINCT ' . DB::qn('ip.personID'))
            ->from(DB::qn('#__organizer_instance_persons', 'ip'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.id', 'ip.instanceID'))
            ->innerJoin(DB::qn('#__organizer_units', 'u'), DB::qc('u.id', 'i.unitID'))
            ->where(DB::qn('u.courseID') . ' = :courseID')->bind(':courseID', $courseID, ParameterType::INTEGER);

        if ($eventID) {
            $query->where(DB::qn('i.eventID') . ' = :eventID')->bind(':eventID', $eventID, ParameterType::INTEGER);
        }

        if ($roleIDs) {
            $query->whereIn(DB::qn('ip.roleID'), $roleIDs);
        }

        DB::setQuery($query);

        if (!$personIDs = DB::loadIntColumn()) {
            return [];
        }

        $persons = [];
        foreach ($personIDs as $personID) {
            $persons[$personID] = Persons::lastNameFirst($personID);
        }

        return $persons;
    }

    /**
     * Checks if the course is a preparatory course.
     *
     * @param   int  $courseID  the id of the course
     *
     * @return bool true if the course is expired, otherwise false
     */
    public static function preparatory(int $courseID): bool
    {
        $query = DB::getQuery();
        $query->select('COUNT(*)')
            ->from(DB::qn('#__organizer_units', 'u'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.unitID', 'u.id'))
            ->innerJoin(DB::qn('#__organizer_events', 'e'), DB::qc('e.id', 'i.eventID'))
            ->where(DB::qn('u.courseID') . ' = :courseID')->bind(':courseID', $courseID, ParameterType::INTEGER)
            ->where(DB::qn('e.preparatory') . ' = 1');

        DB::setQuery($query);

        return DB::loadBool();
    }

    /**
     * Check if user is a speaker.
     *
     * @param   int  $courseID  the optional id of the course
     * @param   int  $personID  the optional id of the person
     *
     * @return bool true if the user is a speaker, otherwise false
     */
    public static function speaks(int $courseID = 0, int $personID = 0): bool
    {
        return self::hasResponsibility($courseID, $personID, Roles::SPEAKER);
    }

    /**
     * Check if user a course supervisor.
     *
     * @param   int  $courseID  the optional id of the course
     * @param   int  $personID  the optional id of the person
     *
     * @return bool true if the user is a supervisor, otherwise false
     */
    public static function supervises(int $courseID = 0, int $personID = 0): bool
    {
        return self::hasResponsibility($courseID, $personID, Roles::SUPERVISOR);
    }

    /**
     * Check if user is a course teacher.
     *
     * @param   int  $courseID  the optional id of the course
     * @param   int  $personID  the optional id of the person
     *
     * @return bool true if the user is a course teacher, otherwise false
     */
    public static function teaches(int $courseID = 0, int $personID = 0): bool
    {
        return self::hasResponsibility($courseID, $personID, Roles::TEACHER);
    }

    /**
     * Check if user is a course tutor.
     *
     * @param   int  $courseID  the optional id of the course
     * @param   int  $personID  the optional id of the person
     *
     * @return bool true if the user is a tutor, otherwise false
     */
    public static function tutors(int $courseID = 0, int $personID = 0): bool
    {
        return self::hasResponsibility($courseID, $personID, Roles::TUTOR);
    }
}
