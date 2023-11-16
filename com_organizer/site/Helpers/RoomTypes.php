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

use THM\Organizer\Adapters\{Application, Database as DB, HTML};

/**
 * Provides general functions for room type access checks, data retrieval and display.
 */
class RoomTypes extends ResourceHelper implements Selectable
{
    use Filtered;
    use Suppressed;

    private const NO = false, YES = true;

    /**
     * @inheritDoc
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::getResources() as $type) {
            $options[] = HTML::option($type['id'], $type['name']);
        }

        return $options;
    }

    /**
     * @inheritDoc
     *
     * @param   bool  $associated  whether the type needs to be associated with a room
     * @param   bool  $suppressed  whether suppressed types should also be included in the result set
     */
    public static function getResources(bool $associated = self::YES, bool $suppressed = self::NO): array
    {
        $tag    = Application::getTag();
        $select = [
            'DISTINCT ' . DB::qn('t') . '.*',
            DB::qn('t.id', 'id'),
            DB::qn("t.name_$tag", 'name'),
        ];

        $query = DB::getQuery();
        $query->select($select)->from(DB::qn('#__organizer_roomtypes', 't'));

        // Unsuppressed or all
        if ($suppressed === self::NO) {
            $query->where(DB::qn('t.suppress') . ' = 0');
        }

        if ($associated === self::YES) {
            $query->innerJoin(DB::qn('#__organizer_rooms', 'r'), DB::qc('r.roomtypeID', 't.id'));
        }
        elseif ($associated === self::NO) {
            $query->leftJoin(DB::qn('#__organizer_rooms', 'r'), DB::qc('r.roomtypeID', 't.id'));
            $query->where(DB::qn('r.roomtypeID') . ' IS NULL');
        }

        self::addResourceFilter($query, 'building', 'b1', 'r');

        // This join is used specifically to filter campuses independent of buildings.
        $query->leftJoin(DB::qn('#__organizer_buildings', 'b2'), DB::qc('b2.id', 'r.buildingID'));
        self::addCampusFilter($query, 'b2');

        $query->order('name');
        DB::setQuery($query);

        return DB::loadAssocList('id');
    }
}
