<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer\Helpers;

trait Active
{
    public const ACTIVE = 1, INACTIVE = 0;

    public const activeStates = [
        self::ACTIVE   => [
            'class'  => 'publish',
            'column' => 'activate',
            'task'   => 'deactivate',
            'tip'    => 'CLICK_TO_DEACTIVATE'
        ],
        self::INACTIVE => [
            'class'  => 'unpublish',
            'column' => 'activate',
            'task'   => 'activate',
            'tip'    => 'CLICK_TO_ACTIVATE'
        ]
    ];
}