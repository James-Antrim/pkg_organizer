<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

use THM\Organizer\Adapters\{Application, Database as DB, HTML};

/**
 * Provides general functions for campus access checks, data retrieval and display.
 */
class CleaningGroups extends ResourceHelper implements Selectable
{
    public const INCLUDED = 1, EXCLUDED = 0;

    public const STATES = [
        self::INCLUDED => [
            'class'  => 'publish',
            'column' => 'relevant',
            'task'   => 'exclude',
            'tip'    => 'ORGANIZER_CLICK_TO_EXCLUDE'
        ],
        self::EXCLUDED => [
            'class'  => 'unpublish',
            'column' => 'relevant',
            'task'   => 'include',
            'tip'    => 'ORGANIZER_CLICK_TO_INCLUDE'
        ]
    ];

    /**
     * @inheritDoc
     *
     * @param   string  $access  any access restriction which should be performed
     */
    public static function getOptions(string $access = ''): array
    {
        $name    = Application::getTag() === 'en' ? 'name_en' : 'name_de';
        $options = [];
        foreach (self::getResources() as $group) {
            $options[] = HTML::option($group['id'], $group[$name]);
        }

        uasort($options, function ($optionOne, $optionTwo) {
            return strcmp($optionOne->text, $optionTwo->text);
        });

        // Any out of sequence indexes cause JSON to treat this as an object
        return array_values($options);
    }

    /**
     * @inheritDoc
     *
     * @param   string  $access  any access restriction which should be performed
     */
    public static function getResources(): array
    {
        $order = Application::getTag() === 'en' ? 'name_en' : 'name_de';
        $query = DB::getQuery();
        $query->select('*')
            ->from(DB::qn('#__organizer_categories', 'c'))
            ->order(DB::qn($order));

        DB::setQuery($query);

        return DB::loadAssocList('id');
    }
}
