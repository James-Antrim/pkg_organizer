<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

use Joomla\Database\{DatabaseQuery, ParameterType};
use THM\Organizer\Adapters\{Database as DB, Input};
use THM\Organizer\Tables\Suppressed as SuppressedTable;

trait Suppressed
{
    public const REVEALED = 0, SUPPRESSED = 1;

    public const SUPPRESSION_STATES = [
        self::REVEALED   => [
            'class'  => 'publish',
            'column' => 'suppress',
            'task'   => 'suppress',
            'tip'    => 'CLICK_TO_SUPPRESS'
        ],
        self::SUPPRESSED => [
            'class'  => 'unpublish',
            'column' => 'suppress',
            'task'   => 'reveal',
            'tip'    => 'CLICK_TO_REVEAL'
        ]
    ];

    /**
     * Retrieves the suppress attribute of the table.
     *
     * @param   int  $resourceID
     *
     * @return bool
     */
    public static function getSuppressed(int $resourceID): bool
    {
        $table = self::getTable();
        if ($table->load($resourceID)) {
            /* @var $table SuppressedTable */
            return (bool) $table->suppress;
        }

        return true;
    }

    /**
     * Adds a query restriction for the suppress column of the aliased table.
     *
     * @param   DatabaseQuery  $query  the query to modify
     * @param   string         $alias  the alias of the table with the active column
     *
     * @return void
     */
    public static function suppressedFilter(DatabaseQuery $query, string $alias): void
    {
        $suppressed = Input::getCMD('active');

        if ($suppressed === self::ALL) {
            return;
        }

        $suppressed = (int) Input::filter($suppressed, 'bool');
        $query->where(DB::qn("$alias.suppress") . ' = :suppress')->bind(':suppress', $suppressed, ParameterType::INTEGER);
    }
}