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
class ResourcesField extends ColoredOptionsField
{
    /**
     * Type
     * @var    String
     */
    protected $type = 'Resources';

    /**
     * Method to get the field options.
     * @return  array  The field option objects.
     */
    protected function getOptions(): array
    {
        $options = parent::getOptions();

        $resources = [
            Text::_('ORGANIZER_CATEGORIES_AND_PROGRAMS') => [
                'text'  => Text::_('ORGANIZER_CATEGORIES_AND_PROGRAMS'),
                'value' => 'cnp'
            ],
            Text::_('ORGANIZER_EVENTS_AND_SUBJECTS')     => [
                'text'  => Text::_('ORGANIZER_EVENTS_AND_SUBJECTS'),
                'value' => 'ens'
            ],
            Text::_('ORGANIZER_GROUPS_AND_POOLS')        => [
                'text'  => Text::_('ORGANIZER_GROUPS_AND_POOLS'),
                'value' => 'gnp'
            ],
            Text::_('ORGANIZER_ORGANIZATIONS')           => [
                'text'  => Text::_('ORGANIZER_ORGANIZATIONS'),
                'value' => 'organizations'
            ],
            Text::_('ORGANIZER_PERSONS')                 => [
                'text'  => Text::_('ORGANIZER_PERSONS'),
                'value' => 'persons'
            ],
            Text::_('ORGANIZER_ROOMS')                   => [
                'text'  => Text::_('ORGANIZER_ROOMS'),
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
