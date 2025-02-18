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

/** @inheritDoc */
class UnitStates extends ColoredOptions
{
    /** @inheritDoc */
    protected function getOptions(): array
    {
        return [
            (object) [
                'text'  => Text::_('ALL_UNITS'),
                'value' => ''
            ],
            (object) [
                'text'  => Text::_('CURRENT_UNITS'),
                'value' => 1
            ],
            (object) [
                'text'  => Text::_('CHANGED_UNITS'),
                'value' => 4
            ],
            (object) [
                'style' => "background-color:#a0cb5b;",
                'text'  => Text::_('NEW_UNITS'),
                'value' => 2
            ],
            (object) [
                'style' => "background-color:#cd8996;",
                'text'  => Text::_('REMOVED_UNITS'),
                'value' => 3
            ]
        ];
    }
}
