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
 * Ensures that documentable helpers implement functions facilitating documentation access checks.
 */
interface Documentable
{
    /**
     * Checks whether the user is authorized to document the given resource.
     *
     * @param   int  $resourceID  the id of the resource to check documentation access for.
     *
     * @return bool
     */
    public static function documentable(int $resourceID): bool;

    /**
     * Retrieves the resources documentable for the user.
     *
     * @return int[]
     */
    public static function documentableIDs(): array;
}
