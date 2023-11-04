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
use THM\Organizer\Adapters\{Application, Database as DB};

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
        $query = DB::getQuery();
        $query->delete(DB::qn('#__organizer_runs'))->where(DB::qn('endDate') . ' < CURDATE()');
        DB::setQuery($query);
        DB::execute();
    }

    /**
     * Method to get a list of resources from the database.
     * @return DatabaseQuery
     */
    protected function getListQuery(): DatabaseQuery
    {
        $this->deleteDeprecated();

        $tag     = Application::getTag();
        $query   = DB::getQuery();
        $select  = DB::qn(['r.id', 'r.run', 'r.termID', 'r.endDate']);
        $aliased = DB::qn(["r.name_$tag", "t.name_$tag"], ['name', 'term']);
        $query->select(array_merge($select, $aliased))
            ->from(DB::qn('#__organizer_runs', 'r'))
            ->innerJoin(DB::qn('#__organizer_terms', 't'), DB::qc('t.id', 'r.termID'))
            ->order(DB::qn('t.startDate') . ', ' . DB::qn('name'));

        $this->setSearchFilter($query, ['name_de', 'name_en']);
        $this->setValueFilters($query, ['termID']);

        return $query;
    }
}