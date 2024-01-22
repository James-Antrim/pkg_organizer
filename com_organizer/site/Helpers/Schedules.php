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

use THM\Organizer\Adapters\Database as DB;
use THM\Organizer\Adapters\User;
use THM\Organizer\Tables\Schedules as Table;

/**
 * Provides general functions for room type access checks, data retrieval and display.
 */
class Schedules implements Schedulable
{
    /**
     * @inheritDoc
     */
    public static function schedulable(int $resourceID): bool
    {
        $table = new Table();

        if (!$table->load($resourceID)) {
            return (bool) Organizations::schedulableIDs();
        }

        return User::instance()->authorise('organizer.schedule', "com_organizer.organization.$table->organizationID");
    }

    /**
     * @inheritDoc
     */
    public static function schedulableIDs(): array
    {
        if (!$organizationIDs = Organizations::schedulableIDs()) {
            return [];
        }

        $idColumn       = DB::qn('id');
        $organizationID = DB::qn('organizationID');

        $query = DB::getQuery();
        $query->select($idColumn)
            ->from(DB::qn('#__organizer_schedules'))
            ->whereIn($organizationID, $organizationIDs);

        DB::setQuery($query);

        return DB::loadIntColumn();
    }
}
