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
 * Class creates a select box for booking instances.
 */
class BookingInstancesField extends OptionsField
{
    /**
     * @var  string
     */
    protected $type = 'BookingInstances';

    /**
     * Returns an array of booking instance options
     * @return stdClass[]  the pool options
     */
    protected function getOptions(): array
    {
        $options   = parent::getOptions();
        $instances = Helpers\Bookings::getInstanceOptions(Input::getID());

        return array_merge($options, $instances);
    }
}
