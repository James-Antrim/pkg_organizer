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
use THM\Organizer\Helpers\{Organizations, Terms};

/** @inheritDoc */
class Schedules extends ListModel
{
    protected $filter_fields = ['organizationID', 'termID'];

    /** @inheritDoc */
    protected function clean(): void
    {
        $query = DB::getQuery();
        $query->delete(DB::qn('#__organizer_schedules'))->whereIn(DB::qn('termID'), Terms::expiredIDs());
        DB::setQuery($query);
        DB::execute();
    }

    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        $tag   = Application::getTag();
        $query = DB::getQuery();

        $aliased      = DB::qn(
            ['o.id', "o.shortName_$tag", 'term.id', "term.name_$tag", 'u.name'],
            ['organizationID', 'organizationName', 'termID', 'termName', 'userName']
        );
        $select       = DB::qn(['s.id', 's.creationDate', 's.creationTime']);
        $createdParts = DB::qn(['s.creationDate', 's.creationTime']);
        $manual       = [$query->concatenate($createdParts, ' ') . ' AS created ', DB::quote(1) . ' AS ' . DB::qn('access')];
        $select       = array_merge($select, $aliased, $manual);

        $query->select($select)
            ->select($query->concatenate($createdParts, ' '))
            ->from(DB::qn('#__organizer_schedules', 's'))
            ->innerJoin(DB::qn('#__organizer_organizations', 'o'), DB::qc('o.id', 's.organizationID'))
            ->innerJoin(DB::qn('#__organizer_terms', 'term'), DB::qc('term.id', 's.termID'))
            ->innerJoin(DB::qn('#__users', 'u'), DB::qc('u.id', 's.userID'))
            ->order(DB::qn('created') . ' DESC');

        $query->whereIn(DB::qn('o.id'), Organizations::schedulableIDs());

        $this->filterValues($query, ['organizationID', 'termID']);

        return $query;
    }

    /** @inheritDoc */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        $filters = Application::getUserRequestState($this->context . '.filter', 'filter', [], 'array');

        if (!array_key_exists('active', $filters) or $filters['active'] === '') {
            $this->state->set('filter.active', -1);
        }
    }
}
