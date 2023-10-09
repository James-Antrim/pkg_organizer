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
 * Class creates a select box for predefined colors.
 */
class InstanceStatesField extends ColoredOptionsField
{
    /**
     * Type
     * @var    String
     */
    protected $type = 'InstanceStates';

    /**
     * Method to get the field options.
     * @return  array  The field option objects.
     */
    protected function getOptions(): array
    {
        return [
            (object) [
                'text' => Helpers\Languages::_('ORGANIZER_CURRENT_INSTANCES'),
                'value' => 1
            ],
            (object) [
                'text' => Helpers\Languages::_('ORGANIZER_CHANGED_INSTANCES'),
                'value' => 4
            ],
            (object) [
                'style' => "background-color:#a0cb5b;",
                'text' => Helpers\Languages::_('ORGANIZER_NEW_INSTANCES'),
                'value' => 2
            ],
            (object) [
                'style' => "background-color:#cd8996;",
                'text' => Helpers\Languages::_('ORGANIZER_REMOVED_INSTANCES'),
                'value' => 3
            ]
        ];
    }
}
