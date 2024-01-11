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
    public static function getCampusID(int $courseID): int
    {
        $course = new Table();

        return $course->load($courseID) ? $course->campusID : 0;
    }

    /**
     * Creates a display of formatted dates for a course
     *
     * @param   int  $courseID  the id of the course to be loaded
     *
     * @return string the dates to display
     */
    public static function getDateDisplay(int $courseID): string
    {
        if ($dates = self::getDates($courseID)) {
            return Dates::getDisplay($dates['startDate'], $dates ['endDate']);
        }

        return '';
    }

    /**
     * Gets the course start and end dates.
     *
     * @param   int  $courseID  id of course to be loaded
     *
     * @return string[]  the start and end date for the given course
     */
    public static function getDates(int $courseID = 0): array
    {
        if (empty($courseID)) {
            return [];
        }

        $query = DB::getQuery();
        $query->select('DISTINCT MIN(startDate) AS startDate, MAX(endDate) AS endDate')
            ->from('#__organizer_units')
            ->where("courseID = $courseID");
        DB::setQuery($query);

        return DB::loadAssoc();
    }

    /**
     * Retrieves events associated with the given course.
     *
     * @param   int  $courseID  the id of the course
     *
     * @return array[] the events associated with the course
     */
    public static function getEvents(int $courseID): array
    {
        $tag   = Application::getTag();
        $query = DB::getQuery();
        $query->select("DISTINCT e.id, e.name_$tag AS name, contact_$tag AS contact")
            ->select("courseContact_$tag AS courseContact, content_$tag AS content, e.description_$tag AS description")
            ->select("organization_$tag AS organization, pretests_$tag AS pretests, preparatory")
            ->from('#__organizer_events AS e')
            ->innerJoin('#__organizer_instances AS i ON i.eventID = e.id')
            ->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
            ->where("u.courseID = $courseID")
            ->order('name ASC');
        DB::setQuery($query);

        if (!$events = DB::loadAssocList()) {
            return [];
        }

        foreach ($events as &$event) {
            $event['speakers'] = self::getPersons($courseID, $event['id'], [Roles::SPEAKER]);
            $event['teachers'] = self::getPersons($courseID, $event['id'], [Roles::TEACHER]);
            $event['tutors']   = self::getPersons($courseID, $event['id'], [Roles::TUTOR]);
        }

        return $events;
    }

    /**
     * Gets an array of participant IDs for a given course, optionally filtered by the participant's status
     *
     * @param   int  $courseID  the course id
     *
     * @return array[] list of participants in course
     */
    public static function getGroupedParticipation(int $courseID): array
    {
        if (empty($courseID)) {
            return [];
        }

        $query = DB::getQuery();
        $tag   = Application::getTag();
        $query->select("pr.id, pr.name_$tag AS program, pr.accredited AS year, COUNT(*) AS participants")
            ->select("d.abbreviation AS degree")
            ->from('#__organizer_programs AS pr')
            ->innerJoin('#__organizer_degrees AS d ON d.id = pr.degreeID')
            ->innerJoin('#__organizer_participants AS pa ON pa.programID = pr.id')
            ->innerJoin('#__organizer_course_participants AS cp ON cp.participantID = pa.id')
            ->where("courseID = $courseID")
            ->order("pr.name_$tag, d.abbreviation, pr.accredited DESC")
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
     * Gets instances associated with the given course.
     *
     * @param   int  $courseID  the id of the course
     *
     * @return int[] the instances which are a part of the course
     */
    public static function getInstanceIDs(int $courseID): array
    {
        $query = DB::getQuery();
        $query->select("DISTINCT i.id")
            ->from('#__organizer_instances AS i')
            ->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
            ->where("u.courseID = $courseID")
            ->order('i.id');
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
    public static function getParticipantIDs(int $courseID): array
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
    public static function getPersons(int $courseID, int $eventID = 0, array $roleIDs = []): array
    {
        $query = DB::getQuery();
        $query->select("DISTINCT ip.personID")
            ->from('#__organizer_instance_persons AS ip')
            ->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
            ->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
            ->where("u.courseID = $courseID");

        if ($eventID) {
            $query->where("i.eventID = $eventID");
        }

        if ($roleIDs) {
            $query->where('ip.roleID IN (' . implode(',', $roleIDs) . ')');
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
     * Retrieves the ids of units associated with the course.
     *
     * @param   int  $courseID  the id of the course with which the units must be associated
     *
     * @return int[] the ids of the associated units
     */
    public static function getUnitIDs(int $courseID): array
    {
        $query = DB::getQuery();
        $query->select('DISTINCT id')->from('#__organizer_units')->where("courseID = $courseID");
        DB::setQuery($query);

        return DB::loadIntColumn();
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
            ->from('#__organizer_instance_persons AS ip')
            ->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
            ->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
            ->where("ip.personID = $personID");

        if ($courseID) {
            $query->where("u.courseID = $courseID");
        }

        if ($roleID) {
            $query->where("ip.roleID = $roleID");
        }

        DB::setQuery($query);

        return DB::loadBool();
    }

    /**
     * Checks if the course is expired
     *
     * @param   int  $courseID  the id of the course
     *
     * @return bool true if the course is expired, otherwise false
     */
    public static function isExpired(int $courseID): bool
    {
        if ($dates = self::getDates($courseID)) {
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
     * Checks if the course is a preparatory course.
     *
     * @param   int  $courseID  the id of the course
     *
     * @return bool true if the course is expired, otherwise false
     */
    public static function isPreparatory(int $courseID): bool
    {
        $query = DB::getQuery();
        $query->select('COUNT(*)')
            ->from('#__organizer_units AS u')
            ->innerJoin('#__organizer_instances AS i ON i.unitID = u.id')
            ->innerJoin('#__organizer_events AS e ON e.id = i.eventID')
            ->where("u.courseID = $courseID")
            ->where('e.preparatory = 1');

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
