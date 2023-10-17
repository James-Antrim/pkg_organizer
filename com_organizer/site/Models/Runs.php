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

use Joomla\Database\DatabaseQuery;
use THM\Organizer\Adapters\{Application, Database, Queries\QueryMySQLi};

/**
 * Class retrieves information for a filtered set of runs.
 */
class Runs extends ListModel
{
    protected string $defaultOrdering = 't.startDate, name';

    protected $filter_fields = ['termID'];

    /**
     * Remove runs which have expired.
     * @return void
     */
    private function deleteDeprecated(): void
    {
        $query = Database::getQuery();
        $query->delete('#__organizer_runs')->where('endDate < CURDATE()');
        Database::setQuery($query);
        Database::execute();
    }

    /**
     * Method to get a list of resources from the database.
     * @return DatabaseQuery
     */
    protected function getListQuery(): DatabaseQuery
    {
        $this->deleteDeprecated();

        $tag = Application::getTag();
        /* @var QueryMySQLi $query */
        $query = Database::getQuery();
        $query->select("r.id, r.name_$tag as name, r.run, r.termID, r.endDate")
            ->select("t.name_$tag as term")
            ->from('#__organizer_runs AS r')
            ->leftJoin('#__organizer_terms AS t ON t.id = r.termID')
            ->order('t.startDate, name');

        $this->setSearchFilter($query, ['name_de', 'name_en']);
        $this->setValueFilters($query, ['termID']);

        return $query;
    }
}