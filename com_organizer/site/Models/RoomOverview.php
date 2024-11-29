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

use Joomla\CMS\Form\Form;
use Joomla\Database\DatabaseQuery;
use THM\Organizer\Adapters\{Application, Database as DB, Input};
use THM\Organizer\Helpers\{Campuses, Dates, Grids, Instances};

/**
 * Retrieves lesson and event data for a filtered set of rooms.
 */
class RoomOverview extends ListModel
{
    public const DAY = 1, WEEK = 2;

    public array $blocks = [];

    public array $dates = [];

    protected int $defaultLimit = 25;

    protected string $defaultOrdering = 'r.name';

    protected $filter_fields = ['campusID', 'buildingID', 'effCapacity', 'roomtypeID'];

    /** @var array Stores the instances for single search */
    public array $instances = [];

    /** @inheritDoc */
    protected function filterFilterForm(Form $form): void
    {
        parent::filterFilterForm($form);

        if (Input::getParams()->get('campusID')) {
            $form->removeField('campusID', 'filter');
            unset($this->filter_fields[array_search('campusID', $this->filter_fields)]);
        }
    }

    /** @inheritDoc */
    public function getItems(): array
    {
        $items = parent::getItems();

        $query = DB::getQuery();
        $query->select('DISTINCT ' . DB::qn('i.id'))
            ->from(DB::qn('#__organizer_instances', 'i'))
            ->innerJoin(DB::qn('#__organizer_blocks', 'b'), DB::qc('b.id', 'i.blockID'))
            ->innerJoin(DB::qn('#__organizer_units', 'u'), DB::qc('u.id', 'i.unitID'))
            ->innerJoin(DB::qn('#__organizer_instance_persons', 'ip'), DB::qc('ip.instanceID', 'i.id'))
            ->innerJoin(DB::qn('#__organizer_instance_rooms', 'ir'), DB::qc('ir.assocID', 'ip.id'))
            ->where(DB::qcs([
                ['b.date', ':date'],
                ['i.delta', 'removed', '!=', true],
                ['ir.delta', 'removed', '!=', true],
                ['ir.delta', 'removed', '!=', true],
                ['ir.roomID', ':roomID'],
                ['u.delta', 'removed', '!=', true],
            ]))
            ->bind(':date', $date)
            ->bind(':roomID', $roomID)
            ->order(DB::qn('b.startTime'));

        $dateAssignment = count($this->blocks) === 1;

        // Blocks displayed
        if (!$dateAssignment) {
            $etRelevance = DB::qcs([['b.startTime', ':startTime1', '>='], ['b.startTime', ':endTime1', '<=']]);
            $stRelevance = DB::qcs([['b.endTime', ':startTime2', '>='], ['b.endTime', ':endTime2', '<=']]);
            $query->where("(($stRelevance) OR ($etRelevance))")
                ->bind(':endTime1', $endTime1)
                ->bind(':endTime2', $endTime2)
                ->bind(':startTime1', $startTime1)
                ->bind(':startTime2', $startTime2);
        }

        foreach ($items as $room) {
            $roomID = $room->id;

            foreach (array_keys($this->dates) as $date) {

                if (!$dateAssignment) {
                    foreach ($this->blocks as $blockNo => $block) {
                        $column     = "$date-$blockNo";
                        $endTime1   = $endTime2 = $block['endTime'];
                        $startTime1 = $startTime2 = $block['startTime'];
                        DB::setQuery($query);
                        $instanceIDs     = DB::loadIntColumn();
                        $room->$column   = $instanceIDs;
                        $this->instances = array_merge($this->instances, $instanceIDs);
                    }
                    continue;
                }

                $column = $date;
                DB::setQuery($query);
                $instanceIDs     = DB::loadIntColumn();
                $room->$column   = $instanceIDs;
                $this->instances = array_merge($this->instances, $instanceIDs);
            }
        }

        $conditions      = ['delta' => false];
        $this->instances = array_flip(array_unique($this->instances));

        foreach (array_keys($this->instances) as $instanceID) {
            if (!$instance = Instances::instance($instanceID)) {
                unset($this->instances[$instanceID]);
                continue;
            }

            Instances::fill($instance, $conditions);
            $this->instances[$instanceID] = $instance;
        }

        return $items;
    }

    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        $tag     = Application::tag();
        $query   = DB::getQuery();
        $aliased = DB::qn(["t.name_$tag", "t.description_$tag"], ['type', 'description']);
        $select  = DB::qn(['r.id', 'r.effCapacity', 'r.roomtypeID', 'r.name']);

        $query->select(array_merge($select, $aliased))
            ->from(DB::qn('#__organizer_rooms', 'r'))
            ->innerJoin(DB::qn('#__organizer_roomtypes', 't'), DB::qc('t.id', 'r.roomtypeID'))
            ->innerJoin(DB::qn('#__organizer_buildings', 'b'), DB::qc('b.id', 'r.buildingID'))
            // Only display active rooms and public room types, i.e. no offices or toilets...
            ->where(DB::qcs([['r.active', 1], ['t.suppress', 0]]));

        $this->filterSearch($query, ['r.name']);
        $this->filterValues($query, ['buildingID', 'roomtypeID']);

        // Complex query structure
        $this->filterByCampus($query, 'b');

        if ($capacity = $this->state->get('filter.effCapacity')) {
            $query->where("r.effCapacity >= $capacity");
        }

        $query->order($this->defaultOrdering);

        return $query;
    }

    /** @inheritDoc */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        $list = Input::getListItems();

        $date = Application::userRequestState("$this->context.list.date", "list_date", '', 'string');
        $date = (string) $list->get('date', $date);
        $date = Dates::standardize($date);

        $defaultGrid = Grids::getDefault();

        if ($campusID = Input::getParams()->get('campusID')) {
            $defaultGrid = Campuses::gridID($campusID);
            $this->setState('filter.campusID', $campusID);
        }

        $campusID   = $this->state->get('filter.campusID');
        $buildingID = $this->state->get('filter.buildingID');

        if ($campusID and $buildingID and !in_array($buildingID, Campuses::buildings($campusID))) {
            $this->setState('filter.buildingID', '');
        }

        $gridID = Application::userRequestState("$this->context.list.gridID", "list_gridID", $defaultGrid, 'int');
        $gridID = (int) $list->get('gridID', $gridID);

        $template = Application::userRequestState("$this->context.list.template", "list_template", self::DAY, 'int');
        $template = (int) $list->get('template', $template);

        $this->setState('list.date', $date);
        $this->setState('list.gridID', $gridID);
        $this->setState('list.template', $template);

        $grid = json_decode(Grids::getGrid($gridID), true);

        // Preformatting for later use in queries here and display in the view.
        if ($template === self::WEEK) {
            $dates       = Dates::week($date, $grid['startDay'], $grid['endDay']);
            $currentDate = $dates['startDate'];
            while ($currentDate <= $dates['endDate']) {
                $this->dates[$currentDate] = Dates::formatDate($currentDate);
                $currentDate               = date('Y-m-d', strtotime("$currentDate + 1 days"));
            }
        }
        else {
            $this->dates[$date] = Dates::formatDate($date);
        }

        foreach ($grid['periods'] as $blockNo => $block) {
            $block['endTime']   = Dates::formatTime($block['endTime']);
            $block['startTime'] = Dates::formatTime($block['startTime']);

            $this->blocks[$blockNo] = $block;
        }
    }
}
