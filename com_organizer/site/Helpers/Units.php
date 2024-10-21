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

use THM\Organizer\Adapters\{Application, Database as DB, User};
use Joomla\Database\ParameterType;
use THM\Organizer\Tables;
use THM\Organizer\Tables\Units as Table;

/**
 * Provides general function for data retrieval and display.
 */
class Units extends ResourceHelper
{
    /**
     * Gets the campus id to associate with a course based on event documentation and planning data.
     *
     * @param   int       $unitID     the id of the unit
     * @param   int|null  $defaultID  the id of a campus associated with an event associated with the unit
     *
     * @return int|null the id of the campus to associate with the course
     */
    public static function getCampusID(int $unitID, ?int $defaultID): ?int
    {
        $query = DB::getQuery();
        $query->select('c.id AS campusID, c.parentID, COUNT(*) AS campusCount')
            ->from('#__organizer_campuses AS c')
            ->innerJoin('#__organizer_buildings AS b ON b.campusID = c.id')
            ->innerJoin('#__organizer_rooms AS r ON r.buildingID = b.id')
            ->innerJoin('#__organizer_instance_rooms AS ir ON ir.roomID = r.id')
            ->innerJoin('#__organizer_instance_persons AS ip ON ip.id = ir.assocID')
            ->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
            ->where("i.unitID = $unitID")
            ->where("r.virtual = 0")
            ->group('c.id')
            ->order('campusCount DESC');
        DB::setQuery($query);

        $plannedCampus = DB::loadAssoc();

        if ($defaultID) {
            if ($plannedCampus['campusID'] === $defaultID or $plannedCampus['parentID'] === $defaultID) {
                return $plannedCampus['campusID'];
            }

            return $defaultID;
        }

        return $plannedCampus['campusID'];
    }

    /**
     * Retrieves the group/category contexts for a given unit/event tub
     *
     * @param   int  $unitID   the unit id
     * @param   int  $eventID  the event id
     *
     * @return array[]
     */
    public static function getContexts(int $unitID, int $eventID): array
    {
        $tag   = Application::tag();
        $query = DB::getQuery();
        $query->select("g.id AS groupID, g.categoryID, g.fullName_$tag AS fqGroup, g.name_$tag AS nqGroup")
            ->from('#__organizer_instances AS i')
            ->innerJoin('#__organizer_instance_persons AS ip ON ip.instanceID = i.id')
            ->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ip.id')
            ->innerJoin('#__organizer_groups AS g ON g.id = ig.groupID')
            ->where("i.eventID = $eventID")
            ->where("i.unitID = $unitID");
        DB::setQuery($query);

        return DB::loadAssocList('groupID');
    }

    /**
     * Retrieves the id of events associated with the resource
     *
     * @param   int       $unitID      the id of the unit
     * @param   int|null  $instanceID  the id of a related instance for temporal restrictions
     *
     * @return int[] the ids of events associated with the resource
     */
    public static function getEventIDs(int $unitID, ?int $instanceID = null): array
    {
        $query = DB::getQuery();

        $query->select('DISTINCT i.eventID')
            ->from('#__organizer_instances AS i')
            ->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
            ->where("unitID = $unitID")
            ->where("i.delta != 'removed'");

        if ($instanceID) {
            $table = new Tables\Instances();
            if ($table->load($instanceID)) {
                $query->where("i.blockID = $table->blockID");
            }
        }

        DB::setQuery($query);

        return DB::loadIntColumn();
    }

    /**
     * Gets a list of distinct names associated with the unit.
     *
     * @param   int  $unitID  the id of the unit
     *
     * @return string[] the names of the associated events
     */
    public static function getEventNames(int $unitID): array
    {
        $tag   = Application::tag();
        $query = DB::getQuery();
        $query->select('DISTINCT ' . DB::qn("name_$tag"))
            ->from(DB::qn('#__organizer_events', 'e'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.eventID', 'e.id'))
            ->where(DB::qn('i.unitID') . ' = :unitID')->bind(':unitID', $unitID, ParameterType::INTEGER);
        DB::setQuery($query);
        return DB::loadColumn();
    }

    /**
     * Retrieves the grid id for the given unit id.
     *
     * @param   int  $unitID  the id of the unit
     *
     * @return int|null
     */
    public static function getGridID(int $unitID): ?int
    {
        $table = new Table();
        $table->load($unitID);

        return $table->gridID;
    }

    /**
     * Retrieves the ids of groups associated with the unit
     *
     * @param   int       $unitID      the id of the unit
     * @param   int|null  $instanceID  the id of a related instance for temporal restrictions
     *
     * @return int[] the ids of groups associated with the unit
     */
    public static function getGroupIDs(int $unitID, ?int $instanceID = null): array
    {
        $query = DB::getQuery();

        $query->select('DISTINCT ig.groupID')
            ->from('#__organizer_instance_groups AS ig')
            ->innerJoin('#__organizer_instance_persons AS ipe ON ipe.id = ig.assocID')
            ->innerJoin('#__organizer_instances AS i ON i.id = ipe.instanceID')
            ->where("i.unitID = $unitID")
            ->where("ig.delta != 'removed'")
            ->where("ipe.delta != 'removed'")
            ->where("i.delta != 'removed'");

        if ($instanceID) {
            $table = new Tables\Instances();
            if ($table->load($instanceID)) {
                $query->where("i.blockID = $table->blockID");
            }
        }

        DB::setQuery($query);

        return DB::loadIntColumn();
    }

    /**
     * Retrieves the ids of organizations associated with the resource
     *
     * @param   int  $resourceID  the id of the resource for which the associated organizations are requested
     *
     * @return int the id of the organization associated with the unit
     */
    public static function getOrganizationID(int $resourceID): int
    {
        $organizationID = 0;

        if ($resourceID) {
            $table = new Table();

            if ($table->load($resourceID)) {
                $organizationID = $table->id;
            }
        }

        return $organizationID;
    }

    /**
     * Retrieves the ids of rooms associated with the unit
     *
     * @param   int       $unitID      the id of the unit
     * @param   int|null  $instanceID  the id of a related instance for temporal restrictions
     *
     * @return int[] the ids of rooms associated with the unit
     */
    public static function getRoomIDs(int $unitID, ?int $instanceID = null): array
    {
        $query = DB::getQuery();

        $query->select('DISTINCT ir.roomID')
            ->from('#__organizer_instance_rooms AS ir')
            ->innerJoin('#__organizer_instance_persons AS ipe ON ipe.id = ir.assocID')
            ->innerJoin('#__organizer_instances AS i ON i.id = ipe.instanceID')
            ->where("i.unitID = $unitID")
            ->where("ir.delta != 'removed'")
            ->where("ipe.delta != 'removed'")
            ->where("i.delta != 'removed'");

        if ($instanceID) {
            $table = new Tables\Instances();
            if ($table->load($instanceID)) {
                $query->where("i.blockID = $table->blockID");
            }
        }

        DB::setQuery($query);

        return DB::loadIntColumn();
    }

    /**
     * Check if person is associated with a unit as a teacher.
     *
     * @param   int  $unitID    the optional id of the unit
     * @param   int  $personID  the optional id of the person
     *
     * @return bool true if the person is a unit teacher, otherwise false
     */
    public static function teaches(int $unitID = 0, int $personID = 0): bool
    {
        $personID = $personID ?: Persons::getIDByUserID(User::id());

        $query = DB::getQuery();
        $query->select('COUNT(*)')
            ->from('#__organizer_instance_persons AS ip')
            ->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
            ->where("ip.personID = $personID")
            ->where('ip.roleID = ' . Roles::TEACHER);

        if ($unitID) {
            $query->where("i.unitID = $unitID");
        }

        DB::setQuery($query);

        return DB::loadBool();
    }
}