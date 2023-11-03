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

use THM\Organizer\Adapters\Input;
use THM\Organizer\Helpers;
use stdClass;

/**
 * Class creates a select box for participation rooms.
 */
class ParticipationRooms extends Options
{
    /**
     * @var  string
     */
    protected $type = 'ParticipationRooms';

    /**
     * Returns an array of booking instance options
     * @return stdClass[]  the pool options
     */
    protected function getOptions(): array
    {
        $bookingID = Helpers\InstanceParticipants::getBookingID(Input::getID());
        $rooms     = Helpers\Bookings::getRoomOptions($bookingID);

        if (count($rooms) === 1) {
            return $rooms;
        }

        $options = parent::getOptions();

        return array_merge($options, $rooms);
    }
}
