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
use THM\Organizer\Adapters\{Application, Database, Input, User};
use Joomla\Database\DatabaseQuery;
use THM\Organizer\Helpers;

/**
 * Class retrieves the data regarding a filtered set of courses.
 */
class Courses extends ListModel
{
    use Helpers\Filtered;

    protected string $defaultOrdering = 'dates';

    protected $filter_fields = ['campusID', 'status', 'termID'];

    /**
     * @inheritDoc
     */
    protected function filterFilterForm(Form $form): void
    {
        parent::filterFilterForm($form);

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

    /**
     * @inheritDoc
     */
    public function getItems(): array
    {
        if (!$items = parent::getItems()) {
            return [];
        }

        $userID = User::id();

        foreach ($items as $item) {
            $item->participants = count(Helpers\Courses::participantIDs($item->id));
            $item->registered   = Helpers\CourseParticipants::state($item->id, $userID);
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $tag   = Application::getTag();
        $query = Database::getQuery();
        $query->select("c.*, c.name_$tag AS name, MIN(u.startDate) AS startDate, MAX(u.endDate) AS endDate")
            ->from('#__organizer_courses AS c')
            ->innerJoin('#__organizer_units AS u ON u.courseID = c.id')
            ->innerJoin('#__organizer_instances AS i ON i.unitID = u.id')
            ->innerJoin('#__organizer_events AS e ON e.id = i.eventID')
            ->group('c.id');

        $direction = $this->state->get('list.direction');

        switch ($this->state->get('list.ordering')) {
            case 'name':
                if ($direction === 'DESC') {
                    $query->order("c.name_$tag DESC");
                }
                else {
                    $query->order("c.name_$tag ASC");
                }
                break;
            case 'dates':
            default:
                if ($direction === 'DESC') {
                    $query->order('u.endDate DESC');
                }
                else {
                    $query->order('u.startDate ASC');
                }
                break;
        }

        $this->filterSearch($query, ['c.name_de', 'c.name_en', 'e.name_de', 'e.name_en']);

        if (Application::backend()) {
            $organizationIDs = implode(',', Helpers\Can::scheduleTheseOrganizations());
            $query->where("u.organizationID in ($organizationIDs)");
        }

        $params      = Input::getParams();
        $preparatory = ($params->get('onlyPrepCourses') or Input::getBool('preparatory'));

        if (!Application::backend() and $preparatory) {
            $query->where('e.preparatory = 1');
        }
        else {
            $this->filterValues($query, ['c.termID']);
        }

        if (empty($this->state->get('filter.status'))) {
            $today = date('Y-m-d');
            $query->where("endDate >= '$today'");
        }

        $this->filterByCampus($query, 'c');

        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        if (!Application::backend()) {
            $params = Input::getParams();

            if ($campusID = $params->get('campusID')) {
                $this->state->set('filter.campusID', $campusID);
            }
        }
    }
}
