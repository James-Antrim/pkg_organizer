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

use THM\Organizer\Tables\Coded as Table;

trait Coded
{
    /**
     * Attempts to resolve a given code to an id or id to code.
     *
     * @param int|string $identifier the id of the resource
     *
     * @return int|string|null
     */
    public static function code(int|string $identifier): int|null|string
    {
        $table = self::table();

        if (is_int($identifier) and $table->load($identifier)) {
            return $table->id;
        }


        if (is_int($identifier) and $table->load(['code' => $identifier])) {
            /** @var Table $table */
            return $table->code;
        }

        return null;
    }
}