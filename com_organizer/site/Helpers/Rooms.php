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

use THM\Organizer\Adapters\{Database, HTML, Input};
use THM\Organizer\Tables;

/**
 * Class provides general functions for retrieving room data.
 */
class Rooms extends ResourceHelper implements Selectable
{
    private const ALL = -1;

    use Active;
    use Filtered;

    /**
     * Resolves a text to a room id.
     *
     * @param   string  $room  the name of the room
     *
     * @return int the id of the entry
     */
    public static function getID(string $room): int
    {
        $table = new Tables\Rooms();

        if ($table->load(['alias' => $room])) {
            return $table->id;
        }

        if ($table->load(['name' => $room])) {
            return $table->id;
        }

        return 0;
    }

    /**
     * @inheritDoc
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::getResources() as $room) {
            if ($room['active']) {
                $options[] = HTML::option($room['id'], $room['name']);
            }
        }

        return $options;
    }

    /**
     * Retrieves the ids for filtered rooms used in events.
     * @return array[] the rooms used in actual events which meet the filter criteria
     */
    public static function getPlannedRooms(): array
    {
        $query = Database::getQuery();
        $query->select('r.id, r.name, r.roomtypeID')
            ->from('#__organizer_rooms AS r')
            ->innerJoin('#__organizer_instance_rooms AS ir ON ir.roomID = r.id')
            ->order('r.name');

        if ($organizationID = Input::getFilterID('organization')) {
            $query->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ir.assocID')
                ->innerJoin('#__organizer_groups AS g ON g.id = ig.groupID')
                ->innerJoin('#__organizer_associations AS a ON a.categoryID = g.categoryID')
                ->where("a.organizationID = $organizationID");

            if ($selectedCategory = Input::getFilterID('category')) {
                $query->where("g.categoryID  = $selectedCategory");
            }
        }

        Database::setQuery($query);

        if (!$results = Database::loadAssocList()) {
            return [];
        }

        $plannedRooms = [];
        foreach ($results as $result) {
            $plannedRooms[$result['name']] = ['id' => $result['id'], 'roomtypeID' => $result['roomtypeID']];
        }

        return $plannedRooms;
    }

    /**
     * @inheritDoc
     */
    public static function getResources(): array
    {
        $query = Database::getQuery();
        $query->select("DISTINCT r.id, r.*")
            ->from('#__organizer_rooms AS r')
            ->innerJoin('#__organizer_roomtypes AS rt ON rt.id = r.roomtypeID')
            ->order('name');
        self::filterResources($query, 'building', 'b1', 'r');

        // TODO Remove roomTypeIDs on completion of migration.
        $roomtypeID  = Input::getInt('roomtypeID', Input::getInt('roomTypeIDs', self::ALL));
        $roomtypeIDs = $roomtypeID ? [$roomtypeID] : Input::getFilterIDs('roomtype');

        if (!in_array(self::ALL, $roomtypeIDs)) {
            $query->where("rt.id IN (" . implode(',', $roomtypeIDs) . ")");
        }

        $active = Input::getInt('active', 1);

        if ($active !== self::ALL) {
            $query->where("r.active = $active");
        }

        $suppress = Input::getInt('suppress');

        if ($suppress !== self::ALL) {
            $query->where("rt.suppress = $suppress");
        }

        // This join is used specifically to filter campuses independent of buildings.
        $query->leftJoin('#__organizer_buildings AS b2 ON b2.id = r.buildingID');
        self::filterCampus($query, 'b2');
        Database::setQuery($query);

        return Database::loadAssocList();
    }

    /**
     * Checks whether the room is virtual.
     *
     * @param   int  $roomID  the id of the room
     *
     * @return bool true if the room is virtual, otherwise false
     */
    public static function isVirtual(int $roomID): bool
    {
        $room = new Tables\Rooms();

        if (!$room->load(($roomID))) {
            return false;
        }

        return (bool) $room->virtual;
    }

    /**
     * Checks whether a given room has been assigned to a given campus.
     *
     * @param   int  $roomID    the id of the room to verify
     * @param   int  $campusID  the id of the campus to check against
     *
     * @return bool
     */
    public static function onCampus(int $roomID, int $campusID): bool
    {
        $query = Database::getQuery();
        $query->select('r.id')
            ->from('#__organizer_rooms AS r')
            ->innerJoin('#__organizer_buildings AS b ON b.id = r.buildingID')
            ->innerJoin('#__organizer_campuses AS c ON b.campusID = c.id')
            ->where("r.id = $roomID")
            ->where("(c.id = $campusID OR c.parentID = $campusID)");
        Database::setQuery($query);

        return Database::loadBool();
    }
}
