<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2024 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

use Joomla\Database\DatabaseQuery;

/**
 * Implementing classes filter queries
 */
interface Filterable
{
    /**
     * Checks whether the user is authorized to document the given resource.
     *
     * @param   DatabaseQuery  $query        the query to modify
     * @param   string         $alias        the alias of the referencing table
     * @param   int[]          $resourceIDs  the id of the resource to filter against
     *
     * @return void
     */
    public static function filterBy(DatabaseQuery $query, string $alias, array $resourceIDs): void;
}
