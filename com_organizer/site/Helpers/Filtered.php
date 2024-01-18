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
