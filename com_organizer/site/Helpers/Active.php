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

use Joomla\Database\{DatabaseQuery, ParameterType};
use THM\Organizer\Adapters\{Database as DB, Input};

trait Active
{
    public const ACTIVE = 1, INACTIVE = 0;

    public const ACTIVE_STATES = [
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

    /**
     * Adds a query restriction for the active column of the aliased table.
     *
     * @param   DatabaseQuery  $query  the query to modify
     * @param   string         $alias  the alias of the table with the active column
     *
     * @return void
     */
    public static function activeFilter(DatabaseQuery $query, string $alias): void
    {
        $active = Input::getCMD('active');

        if ($active === self::ALL) {
            return;
        }

        $active = (int) Input::filter($active, 'bool');
        $query->where(DB::qn("$alias.active") . ' = :active')->bind(':active', $active, ParameterType::INTEGER);
    }
}