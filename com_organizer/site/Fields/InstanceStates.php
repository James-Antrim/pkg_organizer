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

use THM\Organizer\Adapters\Text;

/**
 * Class creates a select box for predefined colors.
 */
class InstanceStates extends ColoredOptions
{
    /**
     * Method to get the field options.
     * @return  array  The field option objects.
     */
    protected function getOptions(): array
    {
        return [
            (object) [
                'text'  => Text::_('CURRENT_INSTANCES'),
                'value' => 1
            ],
            (object) [
                'text'  => Text::_('CHANGED_INSTANCES'),
                'value' => 4
            ],
            (object) [
                'style' => "background-color:#a0cb5b;",
                'text'  => Text::_('NEW_INSTANCES'),
                'value' => 2
            ],
            (object) [
                'style' => "background-color:#cd8996;",
                'text'  => Text::_('REMOVED_INSTANCES'),
                'value' => 3
            ]
        ];
    }
}
