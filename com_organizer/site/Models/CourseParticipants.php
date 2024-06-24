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
use THM\Organizer\Adapters\{Input};

/** @inheritDoc */
class CourseParticipants extends Participants
{
    protected string $defaultOrdering = 'fullName';

    protected $filter_fields = ['attended', 'duplicates', 'paid', 'programID'];

    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        $query = parent::getListQuery();

        $this->filterValues($query, ['attended', 'paid']);

        $courseID = Input::getID();
        $query->select('cp.attended, cp.paid, cp.status')
            ->innerJoin('#__organizer_course_participants AS cp ON cp.participantID = pa.id')
            ->where("cp.courseID = $courseID");

        return $query;
    }
}
