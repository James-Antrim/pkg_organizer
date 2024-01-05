<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

/**
 * Provides functions for XML instance validation and modeling.
 */
class Monitors
{
    public const UPCOMING = 0, DAY_PLAN = 1, MIXED = 2, CONTENT = 3;

    public const DEFAULT = 1, INDIVIDUAL = 0;

    public const LAYOUTS = [self::CONTENT, self::DAY_PLAN, self::MIXED, self::UPCOMING];

    public const CONFIGURATIONS = [
        self::DEFAULT    => [
            'class'  => 'publish',
            'column' => 'useDefaults',
            'task'   => 'individualize',
            'tip'    => 'CLICK_TO_INDIVIDUALIZE'
        ],
        self::INDIVIDUAL => [
            'class'  => 'unpublish',
            'column' => 'useDefaults',
            'task'   => 'useDefaults',
            'tip'    => 'CLICK_TO_USE_DEFAULTS'
        ]
    ];
}
