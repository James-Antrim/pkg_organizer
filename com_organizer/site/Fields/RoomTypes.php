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

/**
 * Class creates a form field for room type selection
 */
class RoomTypes extends Options
{
    /**
     * @var  string
     */
    protected $type = 'RoomTypes';

    /**
     * Method to get the field options.
     * @return  array  The field option objects.
     */
    protected function getOptions(): array
    {
        $options   = parent::getOptions();
        $roomtypes = Helpers\RoomTypes::getOptions();

        return array_merge($options, $roomtypes);
    }
}
