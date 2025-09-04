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

use Joomla\CMS\Form\Field\ListField;
use THM\Organizer\{Adapters\Input, Helpers};

/** @inheritDoc */
class ParticipationInstances extends ListField
{
    /** @inheritDoc */
    protected function getOptions(): array
    {
        $bookingID = Helpers\Participation::bookingID(Input::id());
        $instances = Helpers\Bookings::instanceOptions($bookingID);

        if (count($instances) === 1) {
            return $instances;
        }

        $options = parent::getOptions();

        return array_merge($options, $instances);
    }
}
