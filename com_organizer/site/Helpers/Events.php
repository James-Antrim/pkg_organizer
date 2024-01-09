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
    use Planned;
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
     * @return string[] the names of the categories (or programs)
     */
    public static function getCategoryNames(int $eventID): array
    {
        $names     = [];
        $tag       = Application::getTag();
        $query     = DB::getQuery();
        $nameParts = ["p.name_$tag", "' ('", 'd.abbreviation', "' '", 'p.accredited', "')'"];
        $query->select("c.name_$tag AS category, " . $query->concatenate($nameParts, "") . ' AS program')
            ->select('c.id')
            ->from('#__organizer_categories AS c')
            ->innerJoin('#__organizer_groups AS g ON g.categoryID = c.id')
            ->innerJoin('#__organizer_instance_groups AS ig ON ig.groupID = g.id')
            ->innerJoin('#__organizer_instance_persons AS ip ON ip.id = ig.assocID')
            ->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
            ->leftJoin('#__organizer_programs AS p ON p.categoryID = c.id')
            ->leftJoin('#__organizer_degrees AS d ON p.degreeID = d.id')
            ->where("i.eventID = $eventID");
        DB::setQuery($query);

        if (!$results = DB::loadAssocList()) {
            return [];
        }

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
     * @return int the id of the organization associated with an event, or 0
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
        $query = DB::getQuery();
        $tag   = Application::getTag();
        $query->select("DISTINCT u.id, u.comment, m.abbreviation_$tag AS method")
            ->from('#__organizer_units AS u')
            ->innerJoin('#__organizer_instances AS i ON i.unitID = u.id')
            ->leftJoin('#__organizer_methods AS m ON m.id = i.methodID')
            ->where("eventID = $eventID");
        self::addUnitDateRestriction($query, $date, $interval);
        DB::setQuery($query);

        return DB::loadAssocList();
    }

    /**
     * Check if user is a subject teacher.
     *
     * @param   int  $eventID   the optional id of the subject
     * @param   int  $personID  the optional id of the person entry
     *
     * @return bool true if the user is a teacher, otherwise false
     */
    public static function teaches(int $eventID = 0, int $personID = 0): bool
    {
        $personID = $personID ?: Persons::getIDByUserID(User::id());
        $query    = DB::getQuery();
        $query->select('COUNT(*)')
            ->from('#__organizer_instances AS i')
            ->innerJoin('#__organizer_instance_persons AS ip ON ip.instanceID = i.id')
            ->where("ip.personID = $personID")
            ->where("ip.roleID = 1");

        if ($eventID) {
            $query->where("i.eventID = $eventID");
        }

        DB::setQuery($query);

        return DB::loadBool();
    }
}
