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

use Joomla\Database\{DatabaseQuery, QueryInterface};
use THM\Organizer\Adapters\{Application, Database as DB, Input};
use THM\Organizer\Helpers\Courses as cHelper;

/** @inheritDoc */
class CourseParticipants extends Participants
{
    /** @inheritDoc */
    protected function addAccess(QueryInterface $query): void
    {
        if (cHelper::coordinatable(Input::getID())) {
            $query->select(DB::quote(1) . ' AS ' . DB::qn('access'));
        }
        else {
            $query->select(DB::quote(0) . ' AS ' . DB::qn('access'));
        }
    }

    /** @inheritDoc */
    protected function clean(): void
    {
        // No cleaning in extending views.
    }

    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        $query = parent::getListQuery();

        $this->filterValues($query, ['attended', 'paid']);

        $courseID = Input::getID();
        $query->select(DB::qn(['cp.attended', 'cp.paid', 'cp.status']))
            ->innerJoin(DB::qn('#__organizer_course_participants', 'cp'), DB::qc('cp.participantID', 'pa.id'))
            ->where(DB::qc('cp.courseID', $courseID));

        return $query;
    }

    /** @inheritDoc */
    protected function loadFormData()
    {
        $data = parent::loadFormData();

        if (!property_exists($data, 'hidden')) {
            $data->hidden = [];
        }

        $data->hidden['id']     = $this->state->get('hidden.id');
        $data->hidden['Itemid'] = $this->state->get('hidden.Itemid');

        return $data;
    }

    /** @inheritDoc */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        $context  = 'com_organizer.courseparticipants.hidden';
        $courseID = Application::getUserRequestState("$context.id", 'id', Input::getID(), 'int');
        $itemID   = Application::getUserRequestState("$context.Itemid", 'Itemid', Input::getInt('Itemid'), 'int');

        $this->state->set('hidden.id', $courseID);
        $this->state->set('hidden.Itemid', $itemID);
    }
}
