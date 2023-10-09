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

use THM\Organizer\Helpers;
use stdClass;

/**
 * Class creates a select box for booking instances.
 */
class ParticipationInstancesField extends OptionsField
{
    /**
     * @var  string
     */
    protected $type = 'ParticipationInstances';

    /**
     * Returns an array of booking instance options
     * @return stdClass[]  the pool options
     */
    protected function getOptions(): array
    {
        $bookingID = Helpers\InstanceParticipants::getBookingID(Helpers\Input::getID());
        $instances = Helpers\Bookings::getInstanceOptions($bookingID);

        if (count($instances) === 1) {
            return $instances;
        }

        $options = parent::getOptions();

        return array_merge($options, $instances);
    }
}
