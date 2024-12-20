<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Fields;

use JDatabaseQuery;
use THM\Organizer\Adapters\{Database, HTML, User};
use THM\Organizer\Helpers;
use stdClass;

/**
 * Class creates a select box for booking instances.
 */
class CheckinRooms extends Options
{
    /**
     * Returns an array of booking room options
     * @return stdClass[]  the pool options
     */
    protected function getOptions(): array
    {
        $options       = parent::getOptions();
        $participantID = User::id();

        $now = date('H:i:s');

        // Ongoing
        $query = $this->getQuery($participantID);
        $query->where("b.startTime <= '$now'")->where("b.endTime >= '$now'");
        Database::set($query);

        if (!$instanceID = Database::integer()) {
            // Upcoming
            $then  = date('H:i:s', strtotime('+60 minutes'));
            $query = $this->getQuery($participantID);
            $query->where("b.startTime >= '$now'")->where("b.startTime <= '$then'");
            Database::set($query);

            $instanceID = Database::integer();
        }

        $rooms = [];

        foreach (Helpers\Instances::getRoomIDs($instanceID) as $roomID) {
            $name         = Helpers\Rooms::name($roomID);
            $rooms[$name] = HTML::option($roomID, $name);
        }

        if (count($rooms) === 1) {
            return $rooms;
        }

        ksort($rooms);

        return array_merge($options, $rooms);
    }

    /**
     * Gets a query where common statements are already included.
     *
     * @param   int  $participantID  the id of the participant for which to find checkins
     *
     * @return JDatabaseQuery
     */
    private function getQuery(int $participantID): JDatabaseQuery
    {
        $today = date('Y-m-d');
        $query = Database::query();
        $query->select('instanceID')
            ->from('#__organizer_instance_participants AS ip')
            ->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
            ->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
            ->where("ip.participantID = $participantID")
            ->where("ip.attended = 1")
            ->where("b.date = '$today'");

        return $query;
    }
}
