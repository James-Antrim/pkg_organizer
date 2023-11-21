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

use THM\Organizer\Adapters\{HTML, Text};

/**
 * Class creates a select box for predefined colors.
 */
class Resources extends ColoredOptions
{
    /**
     * Method to get the field options.
     * @return  array  The field option objects.
     */
    protected function getOptions(): array
    {
        $options = parent::getOptions();

        $resources = [
            Text::_('CATEGORIES_AND_PROGRAMS') => [
                'text'  => Text::_('CATEGORIES_AND_PROGRAMS'),
                'value' => 'cnp'
            ],
            Text::_('EVENTS_AND_SUBJECTS')     => [
                'text'  => Text::_('EVENTS_AND_SUBJECTS'),
                'value' => 'ens'
            ],
            Text::_('GROUPS_AND_POOLS')        => [
                'text'  => Text::_('GROUPS_AND_POOLS'),
                'value' => 'gnp'
            ],
            Text::_('ORGANIZATIONS')           => [
                'text'  => Text::_('ORGANIZATIONS'),
                'value' => 'organizations'
            ],
            Text::_('PERSONS')                 => [
                'text'  => Text::_('PERSONS'),
                'value' => 'persons'
            ],
            Text::_('ROOMS')                   => [
                'text'  => Text::_('ROOMS'),
                'value' => 'rooms'
            ]
        ];

        ksort($resources);

        foreach ($resources as $resource) {
            $options[] = HTML::option($resource['value'], $resource['text']);
        }

        return $options;
    }
}
