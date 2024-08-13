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
use THM\Organizer\Adapters\{Application, Database as DB, Input, User};
use THM\Organizer\Helpers\{Can, Courses as Helper, CourseParticipants as CPH, Organizations};

/** @inheritDoc */
class Courses extends ListModel
{
    protected string $defaultOrdering = 'dates';

    protected $filter_fields = ['campusID', 'status', 'termID'];

    /** @inheritDoc */
    protected function filterFilterForm(Form $form): void
    {
        parent::filterFilterForm($form);

        // No management simplification in the administrative part of the site
        if (Application::backend()) {
            return;
        }

        $form->removeField('termID', 'filter');

        $params = Input::getParams();

        if ($params->get('campusID')) {
            $form->removeField('campusID', 'filter');
        }

        if ($params->get('onlyPrepCourses')) {
            $form->removeField('limit', 'list');
            $form->removeField('search', 'filter');
            $form->removeField('status', 'filter');
        }
    }

    /** @inheritDoc */
    public function getItems(): array
    {
        if (!$items = parent::getItems()) {
            return [];
        }

        $userID = User::id();

        foreach ($items as $item) {
            $item->participants = count(Helper::participantIDs($item->id));
            $item->registered   = CPH::state($item->id, $userID);
        }

        return $items;
    }

    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        $tag   = Application::getTag();
        $query = DB::getQuery();
        $url   = 'index.php?option=com_organizer&view=course&id=';

        if (Can::administrate()) {
            $access = DB::quote(1) . ' AS ' . DB::qn('access');
        }
        elseif ($ids = Helper::coordinates()) {
            $access = DB::qn('s.id') . ' IN (' . implode(',', $ids) . ')' . ' AS ' . DB::qn('access');
        }
        else {
            $access = DB::quote(0) . ' AS ' . DB::qn('access');
        }

        $select = [
            DB::qn('c') . '.*',
            DB::qn("c.name_$tag", 'name'),
            'MIN(' . DB::qn('u.startDate') . ') AS ' . DB::qn('startDate'),
            'MAX(' . DB::qn('u.endDate') . ') AS ' . DB::qn('endDate'),
            $query->concatenate([DB::quote($url), DB::qn('c.id')], '') . ' AS ' . DB::qn('url'),
            $access,
        ];

        $query->select($select)
            ->from(DB::qn('#__organizer_courses', 'c'))
            ->innerJoin(DB::qn('#__organizer_units', 'u'), DB::qc('u.courseID', 'c.id'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.unitID', 'u.id'))
            ->innerJoin(DB::qn('#__organizer_events', 'e'), DB::qc('e.id', 'i.eventID'))
            ->group(DB::qn('c.id'));

        $direction = $this->state->get('list.direction') === 'DESC' ? 'DESC' : 'ASC';
        $column    = match ($this->state->get('list.ordering')) {
            'name' => DB::qn("c.name_$tag"),
            default => $direction === 'DESC' ? DB::qn('u.endDate') : DB::qn('u.startDate'),
        };
        $query->order("$column $direction");

        $this->filterSearch($query, ['c.name_de', 'c.name_en', 'e.name_de', 'e.name_en']);

        if ($backend = Application::backend()) {
            $query->whereIn(DB::qn('u.organizationID'), Organizations::schedulableIDs());
        }

        if (!$backend and (Input::getParams()->get('onlyPrepCourses') or Input::getBool('preparatory'))) {
            $query->where('e.preparatory = 1');
        }
        // This filter is otherwise removed
        else {
            $this->filterValues($query, ['c.termID']);
        }

        // Empty state / default is only current / future courses.
        if (empty($this->state->get('filter.status'))) {
            $query->where(DB::qc('endDate', date('Y-m-d'), '>=', true));
        }

        $this->filterByCampus($query, 'c');

        return $query;
    }

    /** @inheritDoc */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        if (!Application::backend() and $campusID = Input::getParams()->get('campusID')) {
            $this->state->set('filter.campusID', $campusID);
        }
    }
}
