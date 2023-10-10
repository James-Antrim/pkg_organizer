<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use JDatabaseQuery;
use THM\Organizer\Adapters\{Application, Database, Queries\QueryMySQLi};

/**
 * Class retrieves information for a filtered set of (schedule) grids.
 */
class Grids extends ListModel
{
    /**
     * Method to get a list of resources from the database.
     * @return JDatabaseQuery
     */
    protected function getListQuery(): JDatabaseQuery
    {
        $tag = Application::getTag();
        /* @var QueryMySQLi $query */
        $query = Database::getQuery();

        $query->select("id, name_$tag AS name, grid, isDefault")
            ->from('#__organizer_grids');
        $this->setOrdering($query);

        return $query;
    }
}
