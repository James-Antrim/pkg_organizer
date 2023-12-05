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

use Joomla\Database\ParameterType;
use THM\Organizer\Adapters\Database as DB;
use THM\Organizer\Tables\Associations as Association;

/**
 * Ensures that resources associated with organizations have functions pertaining to those associations.
 */
abstract class Associated extends ResourceHelper
{
    use Filtered;

    protected static string $resource = '';

    /**
     * Checks whether a given resource is associated with a given organization.
     *
     * @param   int  $organizationID  the id of the organization
     * @param   int  $resourceID      the id of the resource
     *
     * @return bool
     */
    public static function associated(int $organizationID, int $resourceID): bool
    {
        $column      = static::$resource . 'ID';
        $association = new Association();

        return $association->load(['organizationID' => $organizationID, $column => $resourceID]);
    }

    /**
     * The ids of organizations associated with the resource.
     *
     * @param   int  $resourceID  the id of the resource for which the associated organizations are requested
     *
     * @return int[]
     */
    public static function organizationIDs(int $resourceID): array
    {
        $column = DB::qn(static::$resource . 'ID');
        $query  = DB::getQuery();
        $query->select('DISTINCT organizationID')
            ->from(DB::qn('#__organizer_associations'))
            ->where("$column = :resourceID")->bind(':resourceID', $resourceID, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::loadIntColumn();
    }
}
