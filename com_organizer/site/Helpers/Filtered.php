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
use THM\Organizer\Adapters\{Database as DB, Input};

/**
 * Class contains functions for organization filtering.
 */
trait Filtered
{
    /**
     * Restricts the query by the organizationIDs for which the user has the given access right.
     *
     * @param   DatabaseQuery  $query    the query to modify
     * @param   string         $access   the access right to be filtered against
     * @param   string         $context  the resource context from which this function was called
     * @param   string         $alias    the alias being used for the resource table
     *
     * @return void modifies the query object
     */
    public static function filterAccess($query, $access, $context, $alias): void
    {
        $authorized = [];

        switch ($access) {
            case 'document':
                $authorized = Can::documentTheseOrganizations();
                break;
            case 'manage':
                $authorized = Can::manageTheseOrganizations();
                break;
            case 'schedule':
                $authorized = Can::scheduleTheseOrganizations();
                break;
            case 'view':
                $authorized = Can::viewTheseOrganizations();
                break;
        }

        // Alias 'aaf' so as not to conflict with the access filter.
        $authorized = implode(',', $authorized);
        $query->innerJoin(DB::qn('#__organizer_associations', 'aaf'), DB::qc("aaf.{$context}ID", "$alias.id"))
            ->where("aaf.organizationID IN ($authorized)");
    }

    /**
     * Adds a resource filter for a given resource.
     *
     * @param   DatabaseQuery  $query  the query to modify
     * @param   string         $alias  the alias for the linking table
     *
     * @return void modifies the query object
     */
    public static function filterCampus($query, $alias): void
    {
        $campusID  = Input::getInt('campusID');
        $campusIDs = $campusID ? [$campusID] : Input::getFilterIDs('campus');
        if (!$campusIDs) {
            return;
        }

        $condition = DB::qc('campusAlias.id', "$alias.campusID");
        $table     = DB::qn('#__organizer_campuses', 'campusAlias');
        if (in_array('-1', $campusIDs)) {
            $query->leftJoin($table, $condition)->where("campusAlias.id IS NULL");
        }
        else {
            $campusIDs = implode(',', $campusIDs);
            $query->innerJoin($table, $condition)
                ->where("(campusAlias.id IN ($campusIDs) OR campusAlias.parentID IN ($campusIDs))");
        }
    }

    /**
     * Adds a selected organization filter to the query.
     *
     * @param   DatabaseQuery  $query      the query to modify
     * @param   string         $resource   the name of the organization associated resource
     * @param   string         $alias      the alias being used for the resource table
     * @param   string         $keyColumn  the name of the column holding the association key
     *
     * @return void modifies the query
     */
    public static function filterOrganizations($query, $resource, $alias, $keyColumn = 'id'): void
    {
        $organizationID  = Input::getInt('organizationID');
        $organizationIDs = $organizationID ? [$organizationID] : Input::getFilterIDs('organization');
        if (empty($organizationIDs)) {
            return;
        }

        // Alias 'aof' so as not to conflict with the access filter.
        $condition = DB::qc("aof.{$resource}ID", "$alias.$keyColumn");
        $table     = DB::qn('#__organizer_associations', 'aof');
        if (in_array(self::NONE, $organizationIDs)) {
            $query->leftJoin($table, $condition)->where(DB::qn('aof.id') . ' IS NULL');
        }
        else {
            $query->innerJoin($table, $condition)
                ->whereIn(DB::qn('aof.organizationID'), $organizationIDs);
        }
    }

    /**
     * Adds a resource filter for a given resource.
     *
     * @param   DatabaseQuery  $query          the query to modify
     * @param   string         $resource       the name of the resource associated
     * @param   string         $newAlias       the alias for any linked table
     * @param   string         $existingAlias  the alias for the linking table
     *
     * @return void modifies the query
     */
    public static function filterResources($query, $resource, $newAlias, $existingAlias): void
    {
        if ($resource === 'category') {
            if ($categoryID = Input::getInt('programIDs') or $categoryID = Input::getInt('categoryID')) {
                $resourceIDs = [$categoryID];
            }

            $resourceIDs = empty($resourceIDs) ? Input::getIntCollection('categoryIDs') : $resourceIDs;
            $resourceIDs = empty($resourceIDs) ? Input::getFilterIDs('category') : $resourceIDs;
        }
        if ($resource === 'roomtype') {
            $default     = Input::getInt('roomTypeIDs');
            $resourceID  = Input::getInt("{$resource}ID", $default);
            $resourceIDs = $resourceID ? [$resourceID] : Input::getFilterIDs($resource);
        }

        if (empty($resourceIDs)) {
            return;
        }

        $condition = DB::qc("$newAlias.id", "$existingAlias.{$resource}ID");
        $table     = OrganizerHelper::getPlural($resource);
        $table     = DB::qn("#__organizer_$table", $newAlias);
        if (in_array(self::NONE, $resourceIDs)) {
            $query->leftJoin($table, $condition)->where("$newAlias.id IS NULL");
        }
        else {
            $query->innerJoin($table, $condition)->whereIn(DB::qn("$newAlias.id"), $resourceIDs);
        }
    }
}
