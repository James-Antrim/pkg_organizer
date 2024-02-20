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

        return self::associated($organizationIDs, $resourceID);
    }

    /**
     * @inheritDoc
     */
    public static function schedulableIDs(): array
    {
        if (!$organizationIDs = Organizations::schedulableIDs()) {
            return [];
        }

        return self::associatedIDs($organizationIDs);
    }
}
