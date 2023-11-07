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
 * Class retrieves information for a filtered set of holidays.
 */
class Holidays extends ListModel
{
    private const EXPIRED = 1, NOT_EXPIRED = 0;

    protected string $defaultOrdering = 'startDate';

    protected $filter_fields = ['termID', 'type'];

    /**
     * Method to get a list of resources from the database.
     * @return DatabaseQuery
     */
    protected function getListQuery(): DatabaseQuery
    {
        $hED        = DB::qn('h.endDate');
        $conditions = [
            DB::qn('t.startDate') . ' <= ' . DB::qn('h.startDate'),
            DB::qn('t.endDate') . ' >= ' . $hED,
        ];
        $tag        = Application::getTag();
        $query      = DB::getQuery();
        $query->select([DB::qn('h') . '.*', DB::qn("h.name_$tag", 'name'), DB::qn("t.name_$tag", 'term')])
            ->from(DB::qn('#__organizer_holidays', 'h'))
            ->innerJoin(DB::qn('#__organizer_terms', 't'), implode(' AND ', $conditions));

        $this->setIDFilter($query, 't.id', 'filter.termID');
        $this->setSearchFilter($query, ['h.name_de', 'h.name_en']);
        $this->setValueFilters($query, ['type']);

        switch ((int) $this->state->get('filter.status')) {
            case self::EXPIRED:
                $query->where("$hED < CURDATE()");
                break;
            case self::NOT_EXPIRED:
                $query->where("$hED >= CURDATE()");
                break;
        }

        $this->orderBy($query);

        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        $status = (int) $this->state->get('filter.status');
        $this->setState('filter.status', $status);
    }
}