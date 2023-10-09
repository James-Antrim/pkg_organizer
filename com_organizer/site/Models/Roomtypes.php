<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use JDatabaseQuery;
use Organizer\Adapters\Database;
use Organizer\Adapters\Queries\QueryMySQLi;
use Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of room types.
 */
class Roomtypes extends ListModel
{
    protected $filter_fields = ['cleaningID', 'keyID', 'useID'];

    /**
     * Method to get a list of resources from the database.
     * @return JDatabaseQuery
     */
    protected function getListQuery(): JDatabaseQuery
    {
        $tag = Helpers\Languages::getTag();

        /* @var QueryMySQLi $query */
        $query = Database::getQuery();
        $query->select("DISTINCT t.id, t.name_$tag AS name, k.key AS rns")
            ->select($query->concatenate(["c.name_$tag", "' ('", 'c.code', "')'"], '') . ' AS useCode')
            ->from('#__organizer_roomtypes AS t')
            ->innerJoin('#__organizer_use_codes AS c ON c.id = t.usecode')
            ->innerJoin('#__organizer_roomkeys AS k ON k.id = c.keyID')
            ->innerJoin('#__organizer_use_groups AS g ON g.id = k.useID');

        $this->setIDFilter($query, 'k.cleaningID', 'filter.cleaningID');
        $this->setIDFilter($query, 'k.id', 'filter.keyID');
        $this->setIDFilter($query, 'k.useID', 'filter.useID');
        $this->setSearchFilter($query, ['t.name_de', 't.name_en', 't.capacity', 'c.code']);
        $this->setOrdering($query);

        return $query;
    }
}
