<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use Exception;
use Joomla\CMS\Router\Route;
use THM\Organizer\Adapters\Input;
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\OrganizerHelper;
use THM\Organizer\Models;

class CourseParticipants extends Participants
{
    protected $listView = 'course_participants';

    protected $resource = 'course_participant';

    /**
     * Accepts the selected participants into the course.
     * @return void
     * @throws Exception
     */
    public function accept()
    {
        $model = new Models\CourseParticipant();

        if ($model->accept()) {
            OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_SUCCESS', 'success');
        } else {
            OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_FAIL', 'error');
        }

        $url = Helpers\Routing::getRedirectBase() . '&view=course_participants&id=' . Input::getID();
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Prints badges for the selected participants.
     * @return void
     * @throws Exception
     */
    public function attendance()
    {
        Input::set('format', 'pdf');
        Input::set('layout', 'Attendance');
        parent::display();
    }

    /**
     * Sends an circular email to all course participants
     * @return void
     */
    public function notify()
    {
        $model = new Models\CourseParticipant();

        if ($model->notify()) {
            OrganizerHelper::message('ORGANIZER_NOTIFY_SUCCESS', 'success');
        } else {
            OrganizerHelper::message('ORGANIZER_NOTIFY_FAIL', 'error');
        }

        $url = Helpers\Routing::getRedirectBase() . '&view=course_participants&id=' . Input::getID();
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Prints badges for the selected participants.
     * @return void
     * @throws Exception
     */
    public function badges()
    {
        Input::set('format', 'pdf');
        Input::set('layout', 'Badges');
        parent::display();
    }

    /**
     * Prints badges for the selected participants.
     * @return void
     * @throws Exception
     */
    public function participation()
    {
        Input::set('format', 'pdf');
        Input::set('layout', 'Participation');
        parent::display();
    }

    /**
     * Accepts the selected participants into the course.
     * @return void
     */
    public function remove()
    {
        $model = new Models\CourseParticipant();

        if ($model->remove()) {
            OrganizerHelper::message('ORGANIZER_REMOVE_SUCCESS', 'success');
        } else {
            OrganizerHelper::message('ORGANIZER_REMOVE_FAIL', 'error');
        }

        $url = Helpers\Routing::getRedirectBase() . '&view=course_participants&id=' . Input::getID();
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Toggles binary resource properties from a list view.
     * @return void
     * @throws Exception
     */
    public function toggle()
    {
        $model = new Models\CourseParticipant();

        if ($model->toggle()) {
            OrganizerHelper::message('ORGANIZER_TOGGLE_SUCCESS', 'success');
        } else {
            OrganizerHelper::message('ORGANIZER_TOGGLE_FAIL', 'error');
        }

        $url = Helpers\Routing::getRedirectBase() . '&view=course_participants&id=' . Input::getInt('courseID');
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Accepts the selected participants into the course.
     * @return void
     * @throws Exception
     */
    public function waitlist()
    {
        $model = new Models\CourseParticipant();

        if ($model->waitlist()) {
            OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_SUCCESS', 'success');
        } else {
            OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_FAIL', 'error');
        }

        $url = Helpers\Routing::getRedirectBase() . '&view=course_participants&id=' . Input::getID();
        $this->setRedirect(Route::_($url, false));
    }
}