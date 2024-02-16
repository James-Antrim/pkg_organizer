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

use Joomla\Database\{DatabaseQuery, ParameterType};
use THM\Organizer\Adapters\Database as DB;
use THM\Organizer\Tables\Associations as Association;

/**
 * Ensures that resources associated with organizations have functions pertaining to those associations.
 */
abstract class Associated extends ResourceHelper
{
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
     * Adds access filter clauses to the given query.
     *
     * @param   DatabaseQuery  $query   the query to modify
     * @param   string         $alias   the alias being used for the resource table
     * @param   string         $access  the access right to be filtered against
     *
     * @return void
     */
    public static function filterByAccess(DatabaseQuery $query, string $alias, string $access): void
    {
        switch ($access) {
            case 'document':
                $authorized = Organizations::documentableIDs();
                break;
            case 'manage':
                $authorized = Can::manageTheseOrganizations();
                break;
            case 'schedule':
                $authorized = Organizations::schedulableIDs();
                break;
            case 'view':
                $authorized = Can::viewTheseOrganizations();
                break;
            default:
                return;
        }

        // Alias 'aaf' so as not to conflict with the access filter.
        $query->innerJoin(DB::qn('#__organizer_associations', 'aaf'), DB::qc('aaf.' . static::$resource . 'ID', "$alias.id"))
            ->where(DB::qn('aaf.organizationID') . ' IN (' . implode(',', $query->bindArray($authorized)) . ')');
    }

    /**
     * Adds organization filter clauses to the given query. In the associated trait, because tables with an internal column
     * should use the filterByKey function. Explicitly named because of its availability through inheriting classes and not the
     * organizations helper.
     *
     * @param   DatabaseQuery  $query           the query to modify
     * @param   string         $alias           the alias of the table where the campusID is a column
     * @param   int            $organizationID  the id of the organization to use as a filter
     *
     * @return void
     */
    public static function filterByOrganization(DatabaseQuery $query, string $alias, int $organizationID): void
    {
        if ($organizationID === Selectable::UNSELECTED) {
            return;
        }

        $column    = DB::qn('aof.' . static::$resource . 'ID');
        $table     = DB::qn('#__organizer_associations', 'aof');
        $condition = "$column = " . DB::qn("$alias.id");

        if ($organizationID === Selectable::NONE) {
            $query->leftJoin($table, $condition)->where(DB::qn('aof.id') . ' IS NULL');

            return;
        }

        $query->innerJoin($table, $condition)
            ->where(DB::qn('aof.organizationID') . ' = :organizationID')
            ->bind(':organizationID', $organizationID, ParameterType::INTEGER);
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
        $query->select('DISTINCT ' . DB::qn('organizationID'))
            ->from(DB::qn('#__organizer_associations'))
            ->where("$column = :resourceID")->bind(':resourceID', $resourceID, ParameterType::INTEGER);
        DB::setQuery($query);

        return DB::loadIntColumn();
    }
}
