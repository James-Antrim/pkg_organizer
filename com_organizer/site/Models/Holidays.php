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
        $hED   = DB::qn('h.endDate');
        $url   = 'index.php?option=com_organizer&view=holiday&id=';
        $query = DB::getQuery();
        $tag   = Application::tag();

        $access     = [DB::quote(1) . ' AS ' . DB::qn('access')];
        $aliased    = DB::qn(["h.name_$tag", "t.name_$tag"], ['name', 'term']);
        $conditions = [
            DB::qn('t.startDate') . ' <= ' . DB::qn('h.startDate'),
            DB::qn('t.endDate') . ' >= ' . $hED,
        ];
        $url        = [$query->concatenate([DB::quote($url), DB::qn('h.id')], '') . ' AS ' . DB::qn('url')];

        $query->select(array_merge([DB::qn('h') . '.*'], $access, $aliased, $url))
            ->from(DB::qn('#__organizer_holidays', 'h'))
            ->innerJoin(DB::qn('#__organizer_terms', 't'), implode(' AND ', $conditions));

        $this->filterByKey($query, 't.id', 'termID');
        $this->filterSearch($query, ['h.name_de', 'h.name_en']);
        $this->filterValues($query, ['type']);

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

    /** @inheritDoc */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        $status = (int) $this->state->get('filter.status');
        $this->setState('filter.status', $status);
    }
}