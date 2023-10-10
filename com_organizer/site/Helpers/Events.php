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

use THM\Organizer\Adapters\{Application, Database};

/**
 * Provides general functions for subject access checks, data retrieval and display.
 */
class Events extends ResourceHelper
{
    use Planned, Suppressed;

    protected static $resource = 'event';

    /**
     * Check if user is a subject coordinator.
     *
     * @param array|int $eventIDs the optional id of the subject
     * @param int       $personID the optional id of the person entry
     *
     * @return bool true if the user is a coordinator, otherwise false
     */
    public static function coordinates($eventIDs = 0, int $personID = 0): bool
    {
        $personID = $personID ?: Persons::getIDByUserID(Users::getID());
        $query    = Database::getQuery();
        $query->select('COUNT(*)')
            ->from('#__organizer_event_coordinators')
            ->where("personID = $personID");

        if ($eventIDs) {
            $clause = is_array($eventIDs) ? 'eventID IN (' . implode(',', $eventIDs) . ')' : "eventID = $eventIDs";
            $query->where($clause);
        }

        Database::setQuery($query);

        return Database::loadBool();
    }

    /**
     * Looks up the names of categories (or programs) associated with an event.
     *
     * @param int $eventID the id of the event
     *
     * @return string[] the names of the categories (or programs)
     */
    public static function getCategoryNames(int $eventID): array
    {
        $names     = [];
        $tag       = Application::getTag();
        $query     = Database::getQuery();
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
        Database::setQuery($query);

        if (!$results = Database::loadAssocList()) {
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
     * @param int $eventID
     *
     * @return int the id of the organization associated with an event, or 0
     */
    public static function getOrganizationID(int $eventID): int
    {
        // todo implement
        return 0;
    }

    /**
     * Retrieves the units associated with an event.
     *
     * @param int    $eventID  the id of the referenced event
     * @param string $date     the date context for the unit search
     * @param string $interval the interval to use as context for units
     *
     * @return array[]
     */
    public static function getUnits(int $eventID, string $date, string $interval = 'term'): array
    {
        $query = Database::getQuery();
        $tag   = Application::getTag();
        $query->select("DISTINCT u.id, u.comment, m.abbreviation_$tag AS method")
            ->from('#__organizer_units AS u')
            ->innerJoin('#__organizer_instances AS i ON i.unitID = u.id')
            ->leftJoin('#__organizer_methods AS m ON m.id = i.methodID')
            ->where("eventID = $eventID");
        self::addUnitDateRestriction($query, $date, $interval);
        Database::setQuery($query);

        return Database::loadAssocList();
    }

    /**
     * Check if user is a subject teacher.
     *
     * @param int $eventID  the optional id of the subject
     * @param int $personID the optional id of the person entry
     *
     * @return bool true if the user is a teacher, otherwise false
     */
    public static function teaches(int $eventID = 0, int $personID = 0): bool
    {
        $personID = $personID ?: Persons::getIDByUserID(Users::getID());
        $query    = Database::getQuery();
        $query->select('COUNT(*)')
            ->from('#__organizer_instances AS i')
            ->innerJoin('#__organizer_instance_persons AS ip ON ip.instanceID = i.id')
            ->where("ip.personID = $personID")
            ->where("ip.roleID = 1");

        if ($eventID) {
            $query->where("i.eventID = $eventID");
        }

        Database::setQuery($query);

        return Database::loadBool();
    }
}
