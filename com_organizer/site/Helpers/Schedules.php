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
     * Gets a list of schedule ids in the given organization / term context
     *
     * @param   int  $organizationID  the id of the organization context
     * @param   int  $termID          the id of the term context
     *
     * @return array
     */
    public static function contextIDs(int $organizationID, int $termID): array
    {
        $query = DB::query();
        $query->select(DB::qn('id'))
            ->from(DB::qn('#__organizer_schedules'))
            ->where(DB::qcs([['organizationID', $organizationID], ['termID', $termID]]))
            ->order(DB::qn(['creationDate', 'creationTime']));
        DB::set($query);

        return DB::integers();
    }

    /**
     * @inheritDoc
     */
    public static function schedulable(int $resourceID): bool
    {
        $table = new Table();

        if (!$table->load($resourceID)) {
            return (bool) Organizations::schedulableIDs();
        }

        return User::authorise('organizer.schedule', "com_organizer.organization.$table->organizationID");
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

        $query = DB::query();
        $query->select($idColumn)
            ->from(DB::qn('#__organizer_schedules'))
            ->whereIn($organizationID, $organizationIDs);

        DB::set($query);

        return DB::integers();
    }
}
