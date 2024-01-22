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
use THM\Organizer\Adapters\Database as DB;

/**
 * Ensures that resources associated with organizations have functions pertaining to those associations.
 */
abstract class Scheduled extends Associated implements Schedulable
{
    /**
     * @inheritDoc
     */
    public static function schedulable(int $resourceID): bool
    {
        if (!$organizationIDs = Organizations::schedulableIDs()) {
            return false;
        }
        // Scheduling authorization has already been established, allow new
        elseif (!$resourceID) {
            return true;
        }

        $idColumn       = DB::qn('id');
        $organizationID = DB::qn('organizationID');
        $resourceColumn = DB::qn(self::$resource . 'ID');

        $query = DB::getQuery();
        $query->select($idColumn)
            ->from(DB::qn('#__organizer_associations'))
            ->where("$resourceColumn = :resourceID")
            ->bind(':resourceID', $resourceID, ParameterType::INTEGER)
            ->whereIn($organizationID, $organizationIDs);

        DB::setQuery($query);

        return DB::loadBool();
    }

    /**
     * @inheritDoc
     */
    public static function schedulableIDs(): array
    {
        if (!$organizationIDs = Organizations::schedulableIDs()) {
            return [];
        }

        $organizationID = DB::qn('organizationID');
        $column         = DB::qn(self::$resource . 'ID');

        $query = DB::getQuery();
        $query->select("DISTINCT $column")
            ->from(DB::qn('#__organizer_associations'))
            ->where("$column IS NOT NULL")
            ->whereIn($organizationID, $organizationIDs);

        DB::setQuery($query);

        return DB::loadIntColumn();
    }
}
