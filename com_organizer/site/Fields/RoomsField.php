<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Fields;

use Organizer\Helpers;

/**
 * Class creates a form field for room selection.
 */
class RoomsField extends OptionsField
{
    /**
     * @var  string
     */
    protected $type = 'Rooms';

    /**
     * Method to get the field options.
     * @return  array  The field option objects.
     */
    protected function getOptions(): array
    {
        $options = parent::getOptions();
        $rooms   = Helpers\Rooms::getOptions();

        return array_merge($options, $rooms);
    }
}
