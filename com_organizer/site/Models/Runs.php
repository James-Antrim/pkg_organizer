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

/** @inheritDoc */
class Runs extends ListModel
{
    /** @inheritDoc */
    public function __construct($config = [])
    {
        $this->defaultOrdering = 't.startDate, name';
        $this->filter_fields   = ['termID'];
        parent::__construct($config);
    }

    /**
     * Method to get a list of resources from the database.
     * @return DatabaseQuery
     */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::query();
        $tag   = Application::tag();
        $url   = 'index.php?option=com_organizer&view=run&id=';

        // Admin access required for view.
        $access  = [DB::quote(1) . ' AS ' . DB::qn('access')];
        $aliased = DB::qn(["r.name_$tag", "t.name_$tag"], ['name', 'term']);
        $url     = [$query->concatenate([DB::quote($url), DB::qn('r.id')], '') . ' AS ' . DB::qn('url')];
        $select  = DB::qn(['r.id', 'r.run', 'r.termID', 'r.endDate']);

        $query->select(array_merge($select, $access, $aliased, $url))
            ->from(DB::qn('#__organizer_runs', 'r'))
            ->innerJoin(DB::qn('#__organizer_terms', 't'), DB::qc('t.id', 'r.termID'))
            ->order(DB::qn('t.startDate') . ', ' . DB::qn('name'));

        $this->filterSearch($query, ['name_de', 'name_en']);
        $this->filterValues($query, ['termID']);

        return $query;
    }
}