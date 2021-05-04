<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;

use Organizer\Tables;

/**
 * Class provides generalized functions regarding dates and times.
 */
class InstanceParticipants
{
    /**
     * Returns the color value for a given colorID.
     *
     * @param   int  $participationID  the id of the color
     *
     * @return int the id of the booking associated with the participation
     */
    public static function getBookingID(int $participationID): int
    {
        $participation = new Tables\InstanceParticipants();

        if (!$participation->load($participationID)) {
            return 0;
        }

        return Instances::getBookingID($participation->instanceID);
    }
}
