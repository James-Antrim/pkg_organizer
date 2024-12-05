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
use THM\Organizer\Tables\Events as Table;

/**
 * Provides general functions for event access checks, data retrieval and display.
 */
class Events extends Coordinatable implements Schedulable
{
    use Active;
    use Terminated;
    use Suppressed;

    /**
     * Looks up the names of categories (or programs) associated with an event.
     *
     * @param   int  $eventID  the id of the event
     *
     * @return string[]
     */
    public static function categoryNames(int $eventID): array
    {
        $tag   = Application::tag();
        $query = DB::query();

        $nameParts = [DB::qn("p.name_$tag"), "' ('", DB::qn('d.abbreviation'), "' '", DB::qn('p.accredited'), "')'"];
        $program   = [$query->concatenate($nameParts, '') . ' AS ' . DB::qn('program')];
        $selected  = [DB::qn('c.id'), DB::qn("c.name_$tag", 'category')];

        $query->select(array_merge($selected, $program))
            ->from(DB::qn('#__organizer_categories', 'c'))
            ->innerJoin(DB::qn('#__organizer_groups', 'g'), DB::qc('g.categoryID', 'c.id'))
            ->innerJoin(DB::qn('#__organizer_instance_groups', 'ig'), DB::qc('ig.groupID', 'g.id'))
            ->innerJoin(DB::qn('#__organizer_instance_persons', 'ip'), DB::qc('ip.id', 'ig.assocID'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.id', 'ip.instanceID'))
            ->leftJoin(DB::qn('#__organizer_programs', 'p'), DB::qc('p.categoryID', 'c.id'))
            ->leftJoin(DB::qn('#__organizer_degrees', 'd'), DB::qc('p.degreeID', 'd.id'))
            ->where(DB::qn('i.eventID') . ' = :eventID')->bind(':eventID', $eventID, ParameterType::INTEGER);
        DB::set($query);

        if (!$results = DB::arrays()) {
            return [];
        }

        $names = [];
        foreach ($results as $result) {
            $names[$result['id']] = $result['program'] ?: $result['category'];
        }

        return $names;
    }

    /** @inheritDoc */
    protected static function coAccessQuery(array $organizationIDs, int $personID = 0): DatabaseQuery
    {
        $query = DB::query();
        $query->select('DISTINCT ' . DB::qn('e.id'))
            ->from(DB::qn('#__organizer_events', 'e'));

        // Administrators need no filter
        if (!Can::administrate()) {
            $cCondition   = DB::qc('ec.eventID', 'e.id');
            $pRestriction = DB::qc('ec.personID', ':personID');
            $table        = DB::qn('#__organizer_event_coordinators', 'ec');

            // Scheduler access
            if ($organizationIDs) {
                $query->whereIn(DB::qn('e.organizationID'), $organizationIDs);

                // Optional assigned access?
                if ($personID) {
                    $query->leftJoin($table, $cCondition)->where($pRestriction, 'OR')
                        ->bind(':personID', $personID, ParameterType::INTEGER);
                }
            }
            // Strict assigned access
            else {
                $query->innerJoin($table, $cCondition)->where($pRestriction)
                    ->bind(':personID', $personID, ParameterType::INTEGER);
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
            $query->where(DB::qc('e.id', $resourceID));
        }

        DB::set($query);

        return DB::bool();
    }

    /**
     * Gets the ids of the persons explicitly assigned as coordinators for an event.
     *
     * @param   int  $eventID
     *
     * @return int[]
     */
    public static function coordinatorIDs(int $eventID): array
    {
        $query = DB::query();
        $query->select('DISTINCT ' . DB::qn('personID'))
            ->from(DB::qn('#__organizer_event_coordinators'))
            ->where(DB::qc('eventID', $eventID));
        DB::set($query);
        return DB::integers();
    }

    /** @inheritDoc */
    public static function schedulable(int $resourceID): bool
    {
        if (!$organizationIDs = Organizations::schedulableIDs()) {
            return false;
        }
        // Scheduling authorization has already been established, allow new
        elseif (!$resourceID) {
            return true;
        }

        $event = new Table();

        if ($event->load($resourceID)) {
            return in_array($event->organizationID, $organizationIDs);
        }

        Application::error(412);
        return false;
    }

    /** @inheritDoc */
    public static function schedulableIDs(): array
    {
        if (!$organizationIDs = Organizations::schedulableIDs()) {
            return [];
        }

        $query = DB::query();
        $query->select('DISTINCT ' . DB::qn('e.id'))
            ->from(DB::qn('#__organizer_events', 'e'))
            ->whereIn(DB::qn('organizationID'), $organizationIDs);
        DB::set($query);
        return DB::integers();
    }

    /**
     * Check if user is a subject teacher.
     *
     * @param   int  $eventID   the optional id of the subject
     * @param   int  $personID  the optional id of the person entry
     *
     * @return bool
     */
    public static function teaches(int $eventID = 0, int $personID = 0): bool
    {
        $personID = $personID ?: Persons::getIDByUserID(User::id());
        $query    = DB::query();
        $query->select('COUNT(*)')
            ->from(DB::qn('#__organizer_instances', 'i'))
            ->innerJoin(DB::qn('#__organizer_instance_persons', 'ip'), DB::qc('ip.instanceID', 'i.id'))
            ->where(DB::qn('ip.personID') . ' = :personID')->bind(':personID', $personID, ParameterType::INTEGER)
            ->where(DB::qn('ip.roleID') . ' = ' . Roles::TEACHER);

        if ($eventID) {
            $query->where(DB::qn('i.eventID') . ' = :eventID')->bind(':eventID', $eventID, ParameterType::INTEGER);
        }

        DB::set($query);

        return DB::bool();
    }

    /**
     * Retrieves the units associated with an event.
     *
     * @param   int     $eventID   the id of the referenced event
     * @param   string  $date      the date context for the unit search
     * @param   string  $interval  the interval to use as context for units
     *
     * @return array[]
     */
    public static function units(int $eventID, string $date, string $interval = 'term'): array
    {
        [$id, $comment] = DB::qn(['u.id', 'u.comment']);
        $method = DB::qn('m.abbreviation_' . Application::tag(), 'method');

        $query = DB::query();
        $query->select("DISTINCT $id, $comment, $method")
            ->from(DB::qn('#__organizer_units', 'u'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.unitID', 'u.id'))
            ->leftJoin(DB::qn('#__organizer_methods', 'm'), DB::qc('m.id', 'i.methodID'))
            ->where(DB::qn('eventID') . ' = :eventID')->bind(':eventID', $eventID, ParameterType::INTEGER);
        self::terminate($query, $date, $interval);
        DB::set($query);

        return DB::arrays();
    }
}
