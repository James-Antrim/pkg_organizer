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

use Joomla\Database\DatabaseQuery;
use THM\Organizer\Adapters\Database as DB;

/**
 * Ensures that helpers for courses & events implement functions facilitating coordination access checks, not to be confused
 * with subject coordinators who are responsible for subject documentation.
 */
abstract class Coordinatable extends ResourceHelper
{
    /**
     * Builds a query for coordinator access checks. At least one parameter has a real value in the calling function.
     *
     * @param   array  $organizationIDs  the organization IDs for which the user has scheduler access
     * @param   int    $personID         the user's id as a  person resource
     *
     * @return DatabaseQuery
     */
    abstract protected static function coAccessQuery(array $organizationIDs, int $personID = 0): DatabaseQuery;

    /**
     * Checks whether the user is authorized to coordinate the given resource.
     *
     * @param   int  $resourceID  the id of the resource to check documentation access for.
     *
     * @return bool
     */
    abstract public static function coordinatable(int $resourceID = 0): bool;

    /**
     * Retrieves the resources coordinated by the user.
     *
     * @return int[]
     */
    public static function coordinatableIDs(): array
    {
        $organizationIDs = Organizations::schedulableIDs();
        $personID        = Persons::getIDByUserID();

        // Not a scheduler or assigned by one
        if (!$organizationIDs and !$personID) {
            return [];
        }

        $query = static::coAccessQuery($organizationIDs, $personID);
        DB::setQuery($query);

        return DB::loadIntColumn();
    }
}
