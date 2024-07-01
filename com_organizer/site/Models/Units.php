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

use Joomla\Database\{DatabaseQuery, ParameterType};
use THM\Organizer\Adapters\{Application, Database as DB};
use THM\Organizer\Helpers\{Organizations, Terms, Units as Helper};

/** @inheritDoc */
class Units extends ListModel
{
    protected $filter_fields = [
        'gridID',
        'organizationID',
        'status'
    ];

    /**
     * Method to get an array of data items.
     * @return  array  An array of data items on success, false on failure.
     */
    public function getItems(): array
    {
        $items = parent::getItems();

        foreach ($items as $item) {
            $item->name = Helper::getEventNames($item->id);
        }

        return $items;
    }

    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        $modified = date('Y-m-d h:i:s', strtotime('-2 Weeks'));
        $termID   = $this->state->get('filter.termID');
        $query    = DB::getQuery();
        $tag      = Application::getTag();

        $access  = [DB::quote(1) . ' AS ' . DB::qn('access')];
        $aliased = DB::qn(["g.name_$tag", "m.name_de", 'u.delta'], ['grid', 'method', 'status']);
        $select  = DB::qn(['u.id', 'u.code', 'u.courseID', 'u.endDate', 'u.modified', 'u.startDate']);

        //->select("r.name_$tag AS run")
        //->leftJoin(DB::qn('#__organizer_runs', 'r'), DB::qc('r.id', 'u.runID')
        $query->select(array_merge($select, $aliased, $access))
            ->from(DB::qn('#__organizer_units', 'u'))
            ->innerJoin(DB::qn('#__organizer_grids', 'g'), DB::qc('g.id', 'u.gridID'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.unitID', 'u.id'))
            ->innerJoin(DB::qn('#__organizer_instance_persons', 'ip'), DB::qc('ip.instanceID', 'i.id'))
            ->innerJoin(DB::qn('#__organizer_instance_groups', 'ig'), DB::qc('ig.assocID', 'ip.id'))
            ->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.groupID', 'ig.groupID'))
            ->leftJoin(DB::qn('#__organizer_methods', 'm'), DB::qc('m.id', 'i.methodID'))
            ->where('(' . DB::qn('u.delta') . " != 'removed' OR " . DB::qn('u.modified') . ' > :modified)')
            ->bind(':modified', $modified)
            ->where(DB::qn('u.termID') . " = :termID")
            ->bind(':termID', $termID, ParameterType::INTEGER)
            ->order(DB::qn('u.startDate') . ', ' . DB::qn('u.endDate'))
            ->group(DB::qn('u.id'));

        if ($organizationID = $this->state->get('filter.organizationID')) {
            $query->where(DB::qn('a.organizationID') . ' = :organizationID')
                ->bind(':organizationID', $organizationID, ParameterType::INTEGER);
        }
        else {
            $query->whereIn(DB::qn('a.organizationID'), Organizations::schedulableIDs());
        }

        if ($this->state->get('filter.search')) {
            $query->innerJoin(DB::qn('#__organizer_events', 'e'), DB::qc('e.id', 'i.eventID'));
            $this->filterSearch($query, ['e.name_de', 'e.name_en', 'u.code']);
        }

        $this->filterValues($query, ['u.gridID', 'u.runID']);
        $this->filterStatus($query, 'u');

        return $query;
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return void populates state properties
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        if (!$this->state->get('filter.termID')) {
            $this->setState('filter.termID', Terms::currentID());
        }
    }
}
