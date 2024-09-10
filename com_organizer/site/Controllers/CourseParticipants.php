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
use THM\Organizer\Helpers\{Can, Courses as cHelper, CourseParticipants as Helper};
use THM\Organizer\Models;

class CourseParticipants extends Participants
{
    protected string $context = 'courseID';

    /**
     * Sets the participant's registration status to accepted.
     * @return void
     */
    public function accept(): void
    {
        $this->toggleAssoc('status', Helper::ACCEPTED);
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

    /** @inheritDoc */
    protected function authorize(): void
    {
        if ($basic = Can::basic()) {
            return;
        }

        if ($basic === false) {
            Application::error(401);
            return;
        }

        if (!$courseID = Input::getID()) {
            Application::error(400);
            return;
        }

        if (!cHelper::coordinatable($courseID)) {
            Application::error(403);
        }
    }

    /**
     * Prints badges for the selected participants.
     * @return void
     * @throws Exception
     */
    public function badges(): void
    {
        Input::set('format', 'pdf');
        Input::set('layout', 'Badges');
        parent::display();
    }

    /**
     * Sets the participant's attendance status to attended.
     * @return void
     */
    public function confirmAttendance(): void
    {
        $this->toggleAssoc('attended', Helper::ATTENDED);
    }

    /**
     * Sets the participant's payment status to paid.
     * @return void
     */
    public function confirmPayment(): void
    {
        $this->toggleAssoc('paid', Helper::PAID);
    }

    /**
     * Sets the participant's attendance status to unattended.
     * @return void
     */
    public function denyAttendance(): void
    {
        $this->toggleAssoc('attended', Helper::UNATTENDED);
    }

    /**
     * Sets the participant's payment status to paid.
     * @return void
     */
    public function denyPayment(): void
    {
        $this->toggleAssoc('paid', Helper::UNPAID);
    }

    /**
     * Sends an circular email to all course participants
     * @return void
     */
    public function notify(): void
    {
        $model = new Models\CourseParticipant();

        if ($model->notify()) {
            Application::message('ORGANIZER_NOTIFY_SUCCESS');
        }
        else {
            Application::message('ORGANIZER_NOTIFY_FAIL', Application::ERROR);
        }

        //$url = Helpers\Routing::getRedirectBase() . '&view=course_participants&id=' . Input::getID();
        //$this->setRedirect(Route::_($url, false));
    }

    /**
     * Prints badges for the selected participants.
     * @return void
     * @throws Exception
     */
    public function participation(): void
    {
        Input::set('format', 'pdf');
        Input::set('layout', 'Participation');
        parent::display();
    }

    /**
     * Accepts the selected participants into the course.
     * @return void
     */
    public function remove(): void
    {
        $model = new Models\CourseParticipant();

        if ($model->remove()) {
            Application::message('ORGANIZER_REMOVE_SUCCESS');
        }
        else {
            Application::message('ORGANIZER_REMOVE_FAIL', Application::ERROR);
        }

        //$url = Helpers\Routing::getRedirectBase() . '&view=course_participants&id=' . Input::getID();
        //$this->setRedirect(Route::_($url, false));
    }

    /**
     * Sets the participant's registration status to waitlist.
     * @return void
     */
    public function waitlist(): void
    {
        $this->toggleAssoc('status', Helper::WAITLIST);
    }
}