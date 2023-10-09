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
 * Class retrieves the data regarding a filtered set of buildings.
 */
class Roomkeys extends ListModel
{
    protected $filter_fields = ['cleaningID', 'useID'];

    /**
     * Method to get a list of resources from the database.
     * @return JDatabaseQuery
     */
    protected function getListQuery(): JDatabaseQuery
    {
        /* @var QueryMySQLi $query */
        $query = Database::getQuery();
        $tag   = Helpers\Languages::getTag();

        $query->select("rk.*, rk.name_$tag AS name, rk.key AS rns")
            ->select("ug.name_$tag AS useGroup")
            ->select("cg.name_$tag AS cleaningGroup")
            ->from('#__organizer_roomkeys AS rk')
            ->innerJoin('#__organizer_use_groups AS ug ON ug.id = rk.useID');

        $this->setSearchFilter($query, ['name_de', 'name_en']);
        $this->setValueFilters($query, ['cleaningID']);

        $useID = (int) $this->state->get('filter.useID');

        if ($useID) {
            $query->where("useID = $useID");
        }

        if ($cleaningID = (int) $this->state->get('filter.cleaningID')) {
            if ($cleaningID !== self::NONE) {
                $query->innerJoin('#__organizer_cleaning_groups AS cg ON cg.id = rk.cleaningID')
                    ->where("rk.cleaningID = $cleaningID");
            } else {
                $query->where('rk.cleaningID IS NULL');
            }
        } else {
            $query->leftJoin('#__organizer_cleaning_groups AS cg ON cg.id = rk.cleaningID');
        }

        $this->setOrdering($query);

        return $query;
    }
}
