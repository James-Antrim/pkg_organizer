<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2026 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

use THM\Organizer\Adapters\Database as DB;

/**
 * Provides general functions for attendance type access checks, data retrieval and display.
 */
class AttendanceTypes extends ResourceHelper implements Documentable
{
    use Coded;

    /** @inheritDoc */
    public static function documentable(int $resourceID): bool
    {
        if (!Organizations::documentableIDs()) {
            return false;
        }
        // Document authorization has already been established, allow new
        elseif (!$resourceID) {
            return true;
        }

        // These exist beyond organizations. Editing is the purview of administrators.
        return false;
    }

    /** @inheritDoc */
    public static function documentableIDs(): array
    {
        if (Can::administrate()) {
            $query = DB::query();
            $query->select(DB::qn('id'))->from(DB::qn('#__organizer_attendance_types'));
            DB::set($query);
            return DB::integers();
        }

        return [];
    }
}
