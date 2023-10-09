<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;

use Organizer\Tables\Suppressed as SuppressedTable;

trait Suppressed
{
    /**
     * Retrieves the suppress attribute of the table.
     *
     * @param int $resourceID
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
}