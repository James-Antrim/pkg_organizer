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
use THM\Organizer\Adapters\{Database as DB, Input};

/**
 * Ensures that resources associated with organizations have functions pertaining to those associations.
 */
abstract class Associated extends ResourceHelper
{
    protected static string $resource = '';

    /**
     * Checks whether a given resource is associated with a given organization.
     *
     * @param   array|int  $organizationIDs  the id of the organization or organizations
     * @param   int        $resourceID       the id of the resource
     *
     * @return bool
     */
    public static function associated(array|int $organizationIDs, int $resourceID): bool
    {
        $oColumn         = 'organizationID';
        $organizationIDs = is_int($organizationIDs) ? [$organizationIDs] : $organizationIDs;
        $rColumn         = static::$resource . 'ID';

        $query = DB::query();
        $query->select(DB::qn('id'))
            ->from(DB::qn('#__organizer_associations'))
            ->where("$rColumn = :resourceID")
            ->bind(':resourceID', $resourceID, ParameterType::INTEGER)
            ->whereIn(DB::qn($oColumn), $organizationIDs);

        DB::set($query);

        return DB::bool();
    }

    /**
     * Gets the ids of inheriting resources associated with the given organization ids.
     *
     * @param   int[]  $organizationIDs  the organization ids with which the resources should be associated
     *
     * @return array
     */
    public static function associatedIDs(array $organizationIDs): array
    {
        $oColumn = DB::qn('organizationID');
        $rColumn = DB::qn(static::$resource . 'ID');

        $query = DB::query();
        $query->select("DISTINCT $rColumn")
            ->from(DB::qn('#__organizer_associations'))
            ->where("$rColumn IS NOT NULL")
            ->whereIn($oColumn, $organizationIDs);

        DB::set($query);

        return DB::integers();
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
                $authorized = Organizations::manageableIDs();
                break;
            case 'schedule':
                $authorized = Organizations::schedulableIDs();
                break;
            case 'view':
                $authorized = Organizations::viewableIDs();
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
     * @param   DatabaseQuery  $query            the query to modify
     * @param   string         $alias            the alias of the table where the campusID is a column
     * @param   array          $organizationIDs  the id sof the organizations to use as a filter
     *
     * @return void
     */
    public static function filterByOrganizations(DatabaseQuery $query, string $alias, array $organizationIDs): void
    {
        if (!$organizationIDs) {
            return;
        }

        $column    = DB::qn('aof.' . static::$resource . 'ID');
        $table     = DB::qn('#__organizer_associations', 'aof');
        $condition = "$column = " . DB::qn("$alias.id");

        if (in_array(Input::NONE, $organizationIDs)) {
            $query->leftJoin($table, $condition)->where(DB::qn('aof.id') . ' IS NULL');

            return;
        }

        $query->innerJoin($table, $condition)->whereIn(DB::qn('aof.organizationID'), $organizationIDs);
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
        $query  = DB::query();
        $query->select('DISTINCT ' . DB::qn('organizationID'))
            ->from(DB::qn('#__organizer_associations'))
            ->where("$column = :resourceID")->bind(':resourceID', $resourceID, ParameterType::INTEGER);
        DB::set($query);

        return DB::integers();
    }
}
