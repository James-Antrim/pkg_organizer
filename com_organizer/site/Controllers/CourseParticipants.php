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
use THM\Organizer\Adapters\{Application, Database as DB, Input};
use THM\Organizer\Helpers\{Can, Courses as cHelper, CourseParticipants as Helper, Mailer};
use Joomla\Database\ParameterType;
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
    public function attendance(): void
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

    /** @inheritDoc */
    public function delete(): void
    {
        $this->checkToken();
        $this->authorize();

        $courseID    = Input::getID();
        $deleted     = 0;
        $keys        = ['courseID' => $courseID];
        $selectedIDs = Input::getSelectedIDs();
        $selected    = count($selectedIDs);

        $query = DB::getQuery();
        $query->delete(DB::qn('#__organizer_instance_participants'))
            ->whereIn(DB::qn('instanceID'), cHelper::instanceIDs($courseID, true))
            ->where(DB::qc('participantID', ':participantID'))
            ->bind(':participantID', $selectedID, ParameterType::INTEGER);

        foreach ($selectedIDs as $selectedID) {
            $keys['participantID'] = $selectedID;
            $table                 = $this->getTable();

            if ($table->load($keys) and $table->delete()) {
                $deleted++;
            }

            DB::setQuery($query);
            DB::execute();
            Mailer::registrationUpdate($courseID, $selectedID, null);
        }

        $this->farewell($selected, $deleted, true);
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
     * Sets the participant's registration status to waitlist.
     * @return void
     */
    public function waitlist(): void
    {
        $this->toggleAssoc('status', Helper::WAITLIST);
    }
}