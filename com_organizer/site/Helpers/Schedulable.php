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
interface Schedulable
{
    /**
     * Checks whether the user is authorized to plan the given resource.
     *
     * @param   int  $resourceID  the id of the resource to check documentation access for.
     *
     * @return bool
     */
    public static function schedulable(int $resourceID): bool;

    /**
     * Retrieves the resources plannable for the user.
     *
     * @return int[]
     */
    public static function schedulableIDs(): array;
}
