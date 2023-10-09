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

use THM\Organizer\Adapters\Database;
use THM\Organizer\Tables;

/**
 * Class provides generalized functions regarding dates and times.
 */
class InstanceParticipants
{
    /**
     * Returns the color value for a given colorID.
     *
     * @param int $participationID the id of the color
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

    /**
     * Checks if the user has a previous engagement in the previous timeframe
     *
     * @param string $date      the date of the search
     * @param string $startTime the
     * @param string $endTime
     *
     * @return bool
     */
    public static function isBusy(string $date, string $startTime, string $endTime): bool
    {
        $userID = Users::getID();

        $timeConditions = [
            "b.startTime <= '$startTime' AND b.endTime >= '$endTime'",
            "b.startTime <= '$startTime' AND b.endTime > '$startTime'",
            "b.startTime < '$endTime' AND b.endTime >= '$endTime'"
        ];
        $timeConditions = '((' . implode(') OR (', $timeConditions) . '))';

        $query = Database::getQuery();
        $query->select('ip.id')
            ->from('#__organizer_instance_participants AS ip')
            ->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
            ->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
            ->where($timeConditions)
            ->where("b.date = '$date'")
            ->where("ip.participantID = $userID");
        Database::setQuery($query);

        return Database::loadBool();
    }
}
