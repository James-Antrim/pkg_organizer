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
class BookingInstances extends ListField
{
    /** @inheritDoc */
    protected function getOptions(): array
    {
        $options   = parent::getOptions();
        $instances = Helpers\Bookings::instanceOptions(Input::id());

        return array_merge($options, $instances);
    }
}
