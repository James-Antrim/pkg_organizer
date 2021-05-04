<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use JDatabaseQuery;
use Joomla\CMS\Form\Form;
use Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of participants.
 */
class CourseParticipants extends Participants
{
    protected $defaultOrdering = 'fullName';

    protected $filter_fields = ['attended', 'duplicates', 'paid', 'programID'];

    /**
     * Method to get a list of resources from the database.
     *
     * @return JDatabaseQuery
     */
    protected function getListQuery()
    {
        $query = parent::getListQuery();

        $this->setValueFilters($query, ['attended', 'paid']);

        $courseID = Helpers\Input::getID();
        $query->select('cp.attended, cp.paid, cp.status')
            ->innerJoin('#__organizer_course_participants AS cp ON cp.participantID = pa.id')
            ->where("cp.courseID = $courseID");

        return $query;
    }
}
