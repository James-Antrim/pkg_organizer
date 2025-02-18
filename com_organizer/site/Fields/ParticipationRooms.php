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
class ParticipationRooms extends ListField
{
    /** @inheritDoc */
    protected function getOptions(): array
    {
        $bookingID = Helpers\Participation::bookingID(Input::getID());
        $rooms     = Helpers\Bookings::roomOptions($bookingID);

        if (count($rooms) === 1) {
            return $rooms;
        }

        $options = parent::getOptions();

        return array_merge($options, $rooms);
    }
}
