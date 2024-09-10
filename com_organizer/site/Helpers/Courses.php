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

use Joomla\Database\{DatabaseQuery, ParameterType};
use THM\Organizer\Adapters\{Application, Database as DB, User};
use THM\Organizer\Tables\{Courses as Table, Events as eTable};

/**
 * Provides general functions for course access checks, data retrieval and display.
 */
class Courses extends Coordinatable
{
    public const OPEN = null, FIFO = 0, MANUAL = 1;

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

    /** @inheritDoc */
    protected static function coAccessQuery(array $organizationIDs, int $personID = 0): DatabaseQuery
    {
        $query = DB::getQuery();
        $query->select('DISTINCT ' . DB::qn('c.id'))
            ->from(DB::qn('#__organizer_courses', 'c'));

        // Administrators need no filter
        if (!Can::administrate()) {
            $query->innerJoin(DB::qn('#__organizer_units', 'u'), DB::qc('u.courseID', 'c.id'));

            $cCondition   = DB::qc('c.eventID', 'e.id');
            $cTable       = DB::qn('#__organizer_event_coordinators', 'c');
            $eCondition   = DB::qc('e.id', 'ec.eventID');
            $eTable       = DB::qn('#__organizer_events', 'e');
            $iCondition   = DB::qc('i.unitID', 'u.id');
            $iTable       = DB::qn('#__organizer_instances', 'i');
            $pRestriction = DB::qc('c.personID', ':personID');

            // Check for scheduler access
            if ($organizationIDs) {
                $query->whereIn(DB::qn('u.organizationID'), $organizationIDs);

                // Optional assigned access?
                if ($personID) {
                    $query->leftJoin($iTable, $iCondition)->leftJoin($eTable, $eCondition)->leftJoin($cTable, $cCondition)
                        ->where($pRestriction, 'OR')->bind(':personID', $personID, ParameterType::INTEGER);
                }

            }
            // Strict assigned access
            else {
                $query->innerJoin($iTable, $iCondition)->innerJoin($eTable, $eCondition)->innerJoin($cTable, $cCondition)
                    ->where($pRestriction)->bind(':personID', $personID, ParameterType::INTEGER);
            }
        }

        return $query;
    }

    /** @inheritDoc */
    public static function coordinatable(int $resourceID = 0): bool
    {
        $basic = Can::basic();
        if (is_bool($basic)) {
            return $basic;
        }

        $organizationIDs = Organizations::schedulableIDs();
        $personID        = Persons::getIDByUserID();

        // Not a scheduler or assigned by one
        if (!$organizationIDs and !$personID) {
            return false;
        }

        $query = self::coAccessQuery($organizationIDs, $personID);

        if ($resourceID) {
            $query->where(DB::qc('c.id', $resourceID));
        }

        DB::setQuery($query);

        return DB::loadBool();
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
            return Dates::intervalText($dates['startDate'], $dates ['endDate']);
        }

        return '';
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
     * Creates a new course automatically from unit data according to the requesting view.
     *
     * @param   Table  $course   the course being created
     * @param   array  $unitIDs  the ids of the units which will serve as a basis for the course
     * @param   int    $termID   the term context of the course
     * @param   bool   $import   the requesting view is the import courses view
     *
     * @return void
     */
    public static function fromUnits(Table $course, array $unitIDs, int $termID, bool $import = false): void
    {
        sort($unitIDs);

        $eventIDs = [];

        foreach ($unitIDs as $unitID) {
            $eventIDs = array_merge($eventIDs, Units::getEventIDs($unitID));
        }

        $event    = new eTable();
        $eventIDs = array_unique($eventIDs);

        foreach ($eventIDs as $eventID) {
            $event->load($eventID);

            // The name for a non-imported course
            if (!$import) {
                if ($course->name_de === null) {
                    $course->name_de = $event->name_de;
                }
                elseif (!strpos($course->name_de, $event->name_de)) {
                    $course->name_de .= ' / ' . $event->name_de;
                }

                if ($course->name_en === null) {
                    $course->name_en = $event->name_en;
                }
                elseif (!strpos($course->name_en, $event->name_en)) {
                    $course->name_en .= ' / ' . $event->name_en;
                }
            }

            if (empty($course->deadline) or $event->deadline < $course->deadline) {
                $course->deadline = $event->deadline;
            }

            if (empty($course->fee) or $event->fee < $course->fee) {
                $course->fee = $event->fee;
            }

            if (empty($course->maxParticipants) or $event->maxParticipants < $course->maxParticipants) {
                $course->maxParticipants = $event->maxParticipants;
            }

            if ($course->registrationType === null or $event->registrationType < $course->registrationType) {
                $course->registrationType = $event->registrationType;
            }
        }

        $course->campusID = Units::getCampusID(reset($unitIDs), $event->campusID);
        $course->termID   = $termID;
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

    /**
     * Gets instances associated with the given course optionally filtered by future or past.
     *
     * @param   int        $courseID the id of the course
     * @param   bool|null  $future   whether to filter for future instances (null => no filter, 0 => past, 1 => future)
     *
     * @return array
     */
    public static function instanceIDs(int $courseID, bool|null $future = null): array
    {
        $query = DB::getQuery();
        $query->select('DISTINCT ' . DB::qn('i.id'))
            ->from(DB::qn('#__organizer_instances', 'i'))
            ->innerJoin(DB::qn('#__organizer_units', 'u'), DB::qc('u.id', 'i.unitID'))
            ->where(DB::qc('u.courseID', ':courseID'))->bind(':courseID', $courseID, ParameterType::INTEGER)
            ->order(DB::qn('i.id'));

        if ($future !== null) {
            $now     = date('H:i:s');
            $today   = date('Y-m-d');
            $isToday = DB::qc('b.date', $today, '=', true);

            if ($future) {
                $conditionOne = DB::qc('b.date', $today, '>', true);
                $conditionTwo = DB::qc('b.startTime', $now, '>', true);
            }
            else {
                $conditionOne = DB::qc('b.date', $today, '<', true);
                $conditionTwo = DB::qc('b.startTime', $now, '<', true);
            }

            $query->innerJoin(DB::qn('#__organizer_blocks', 'b'), DB::qc('b.id', 'i.blockID'))
                ->where("($conditionOne OR ($isToday AND $conditionTwo))");
        }

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
