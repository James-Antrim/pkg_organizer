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


trait Numbered
{
    /**
     * Gets the resource ids.
     * @return int[] the ids of the resource.
     */
    public static function getIDs(): array
    {
        $ids = array_keys(self::getResources());
        sort($ids);

        return $ids;
    }
}