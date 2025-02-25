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

/**
 * Ensures that viewable helpers implement functions facilitating view access checks.
 */
interface Viewable
{
    /**
     * Checks whether the user is authorized to view the given resource.
     *
     * @param   int  $resourceID  the id of the resource to check view access for.
     *
     * @return bool
     */
    public static function viewable(int $resourceID): bool;

    /**
     * Retrieves the resources with view access for the user.
     *
     * @return int[]
     */
    public static function viewableIDs(): array;
}
