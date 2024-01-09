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
use THM\Organizer\Tables\Events as Table;

/**
 * Provides general functions for subject access checks, data retrieval and display.
 */
class Events extends ResourceHelper
{
    use Active;
    use Terminated;
    use Suppressed;

    /**
     * Gets a list of event ids for which the user has coordinating access.
     * @return int[]
     */
    public static function coordinates(): array
    {
        $query = DB::getQuery();
        $query->select('DISTINCT ' . DB::qn('e.id'))
            ->from(DB::qn('#__organizer_events', 'e'));

        // Administrators need no filter
        if (!Can::administrate()) {

            $condition = DB::qc('ec.eventID', 'e.id');
            $personID  = Persons::getIDByUserID();
            $table     = DB::qn('#__organizer_event_coordinators', 'ec');

            // Check for planer access
            if ($organizationIDs = Can::scheduleTheseOrganizations()) {
                $query->whereIn(DB::qn('e.organizationID'), $organizationIDs);

                // Coordinator entries are on-top
                if ($personID) {
                    $query->leftJoin($table, $condition)
                        ->where(DB::qn('ec.personID') . ' = :personID', 'OR')
                        ->bind(':personID', $personID, ParameterType::INTEGER);
                }
            }
            // Coordinator entries are strictly necessary
            elseif ($personID) {
                $query->innerJoin($table, $condition)
                    ->where(DB::qn('ec.personID') . ' = :personID')
                    ->bind(':personID', $personID, ParameterType::INTEGER);
            }
            // No potential for access.
            else {
                return [];
            }
        }

        DB::setQuery($query);

        return DB::loadIntColumn();
    }

    /**
     * Looks up the names of categories (or programs) associated with an event.
     *
     * @param   int  $eventID  the id of the event
     *
     * @return string[]
     */
    public static function getCategoryNames(int $eventID): array
    {
        $tag   = Application::getTag();
        $query = DB::getQuery();

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
        DB::setQuery($query);

        if (!$results = DB::loadAssocList()) {
            return [];
        }

        $names = [];
        foreach ($results as $result) {
            $names[$result['id']] = $result['program'] ?: $result['category'];
        }

        return $names;
    }

    /**
     * Gets the id of the organization with which an event is associated.
     *
     * @param   int  $eventID
     *
     * @return int
     */
    public static function getOrganizationID(int $eventID): int
    {
        /** @var Table $table */
        $table = self::getTable();

        if ($table->load($eventID)) {
            return $table->organizationID;
        }

        return 0;
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
    public static function getUnits(int $eventID, string $date, string $interval = 'term'): array
    {
        [$id, $comment] = DB::qn(['u.id', 'u.comment']);
        $method = DB::qn('m.abbreviation_' . Application::getTag(), 'method');

        $query = DB::getQuery();
        $query->select("DISTINCT $id, $comment, $method")
            ->from(DB::qn('#__organizer_units', 'u'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.unitID', 'u.id'))
            ->leftJoin(DB::qn('#__organizer_methods', 'm'), DB::qc('m.id', 'i.methodID'))
            ->where(DB::qn('eventID') . ' = :eventID')->bind(':eventID', $eventID, ParameterType::INTEGER);
        self::terminate($query, $date, $interval);
        DB::setQuery($query);

        return DB::loadAssocList();
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
        $query    = DB::getQuery();
        $query->select('COUNT(*)')
            ->from(DB::qn('#__organizer_instances', 'i'))
            ->innerJoin(DB::qn('#__organizer_instance_persons', 'ip'), DB::qc('ip.instanceID', 'i.id'))
            ->where(DB::qn('ip.personID') . ' = :personID')->bind(':personID', $personID, ParameterType::INTEGER)
            ->where(DB::qn('ip.roleID') . ' = ' . Roles::TEACHER);

        if ($eventID) {
            $query->where(DB::qn('i.eventID') . ' = :eventID')->bind(':eventID', $eventID, ParameterType::INTEGER);
        }

        DB::setQuery($query);

        return DB::loadBool();
    }
}
