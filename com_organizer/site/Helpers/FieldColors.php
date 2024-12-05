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
 * Provides general functions for room type access checks, data retrieval and display.
 */
class FieldColors implements Documentable
{
    /**
     * @inheritDoc
     */
    public static function documentable(int $resourceID): bool
    {
        if (!$organizationIDs = Organizations::documentableIDs()) {
            return false;
        }
        // Document authorization has already been established, allow new
        elseif (!$resourceID) {
            return true;
        }

        $idColumn       = DB::qn('id');
        $organizationID = DB::qn('organizationID');

        $query = DB::query();
        $query->select($idColumn)
            ->from(DB::qn('#__organizer_field_colors'))
            ->where("$idColumn = :resourceID")
            ->bind(':resourceID', $resourceID, ParameterType::INTEGER)
            ->whereIn($organizationID, $organizationIDs);

        DB::set($query);

        return DB::bool();
    }

    /**
     * @inheritDoc
     */
    public static function documentableIDs(): array
    {
        if (!$organizationIDs = Organizations::documentableIDs()) {
            return [];
        }

        $idColumn       = DB::qn('id');
        $organizationID = DB::qn('organizationID');

        $query = DB::query();
        $query->select($idColumn)
            ->from(DB::qn('#__organizer_field_colors'))
            ->whereIn($organizationID, $organizationIDs);

        DB::set($query);

        return DB::integers();
    }
}
