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
use THM\Organizer\Adapters\{Database as DB, HTML, Input};
use THM\Organizer\Tables\Rooms as Table;

/**
 * Class provides general functions for retrieving room data.
 */
class Rooms extends ResourceHelper implements Selectable
{
    //VIRTUAL = 1
    public const PHYSICAL = 0;

    use Active;
    use Filtered;
    use Suppressed;

    /**
     * Resolves a text to a room id.
     *
     * @param   string  $room  the name of the room
     *
     * @return int the id of the entry
     */
    public static function getID(string $room): int
    {
        $table = new Table();

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
        $query = DB::getQuery();
        $query->select(DB::qn(['r.id', 'r.name', 'r.roomtypeID']))
            ->from(DB::qn('#__organizer_rooms', 'r'))
            ->innerJoin(DB::qn('#__organizer_instance_rooms', 'ir'), DB::qc('ir.roomID', 'r.id'))
            ->order(DB::qn('r.name'));

        if ($organizationID = Input::getFilterID('organization')) {
            $query->innerJoin(DB::qn('#__organizer_instance_groups', 'ig'), DB::qc('ig.assocID', 'ir.assocID'))
                ->innerJoin(DB::qn('#__organizer_groups', 'g'), DB::qc('g.id', 'ig.groupID'))
                ->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.categoryID', 'g.categoryID'))
                ->where("a.organizationID = $organizationID");

            if ($selectedCategory = Input::getFilterID('category')) {
                $query->where("g.categoryID  = $selectedCategory");
            }
        }

        DB::setQuery($query);

        if (!$results = DB::loadAssocList()) {
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
        $query = DB::getQuery();
        $query->select(['DISTINCT ' . DB::qn('r.id'), DB::qn('r') . '.*'])
            ->from(DB::qn('#__organizer_rooms', 'r'))
            ->innerJoin(DB::qn('#__organizer_roomtypes', 'rt'), DB::qc('rt.id', 'r.roomtypeID'))
            ->order(DB::qn('name'));
        self::filterResources($query, 'building', 'b1', 'r');

        if ($typeID = Input::getInt('roomtypeID')) {
            $query->where(DB::qn('rt.id') . ' = :typeID')->bind(':typeID', $typeID, ParameterType::INTEGER);
        }

        self::activeFilter($query, 'r');
        self::suppressedFilter($query, 'r');

        if ($campusID = Input::getInt('campusID')) {
            $query->leftJoin(DB::qn('#__organizer_buildings', 'b2'), DB::qc('b2.id', 'r.buildingID'));
            Campuses::filter($query, 'b2', $campusID);
        }

        DB::setQuery($query);

        return DB::loadAssocList();
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
        $room = new Table();

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
        $cID = DB::qn('c.id');
        $pID = DB::qn('c.parentID');
        $rID = DB::qn('r.id');

        $query = DB::getQuery();
        $query->select($rID)
            ->from(DB::qn('#__organizer_rooms', 'r'))
            ->innerJoin(DB::qn('#__organizer_buildings', 'b'), DB::qc('b.id', 'r.buildingID'))
            ->innerJoin(DB::qn('#__organizer_campuses', 'c'), DB::qc('b.campusID', 'c.id'))
            ->where("($cID = :campusID OR $pID = :parentID)")
            ->where("$rID = :roomID")
            ->bind(':campusID', $campusID, ParameterType::INTEGER)
            ->bind(':parentID', $campusID, ParameterType::INTEGER)
            ->bind(':roomID', $roomID, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::loadBool();
    }
}
