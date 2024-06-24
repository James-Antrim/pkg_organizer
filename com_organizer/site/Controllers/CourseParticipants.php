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
use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers;
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
            Application::message('ORGANIZER_STATUS_CHANGE_SUCCESS');
        }
        else {
            Application::message('ORGANIZER_STATUS_CHANGE_FAIL', Application::ERROR);
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
            Application::message('ORGANIZER_NOTIFY_SUCCESS');
        }
        else {
            Application::message('ORGANIZER_NOTIFY_FAIL', Application::ERROR);
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
            Application::message('ORGANIZER_REMOVE_SUCCESS');
        }
        else {
            Application::message('ORGANIZER_REMOVE_FAIL', Application::ERROR);
        }

        $url = Helpers\Routing::getRedirectBase() . '&view=course_participants&id=' . Input::getID();
        $this->setRedirect(Route::_($url, false));
    }

    /** @inheritDoc */
    public function toggle()
    {
        $model = new Models\CourseParticipant();

        if ($model->toggle()) {
            Application::message('ORGANIZER_TOGGLE_SUCCESS');
        }
        else {
            Application::message('ORGANIZER_TOGGLE_FAIL', Application::ERROR);
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
            Application::message('ORGANIZER_STATUS_CHANGE_SUCCESS');
        }
        else {
            Application::message('ORGANIZER_STATUS_CHANGE_FAIL', Application::ERROR);
        }

        $url = Helpers\Routing::getRedirectBase() . '&view=course_participants&id=' . Input::getID();
        $this->setRedirect(Route::_($url, false));
    }
}