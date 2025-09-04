<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use THM\Organizer\Adapters\{Application, Input, User};
use THM\Organizer\Helpers\{Can, Courses, CourseParticipants as cpHelper, Mailer};
use THM\Organizer\Tables\{CourseParticipants as cpTable, InstanceParticipants as ipTable};

/** @inheritDoc */
class Course extends FormController
{
    protected string $list = 'Courses';

    /**
     * Removes a participant's registration for a course and related instance registrations.
     *
     * @return void
     */
    public function deregister(): void
    {
        $this->checkToken();

        $referrer = Input::instance()->server->getString('HTTP_REFERER');

        if (!$courseID = Input::id() or !$participantID = User::id()) {
            Application::message('400', Application::WARNING);
            $this->setRedirect($referrer);
            return;
        }

        if (!Can::manage('participant', $participantID) and !Courses::coordinatable($courseID)) {
            Application::error(403);
            return;
        }

        $dates = Courses::dates($courseID);

        if (empty($dates['endDate'])) {
            Application::message('412', Application::WARNING);
            $this->setRedirect($referrer);
            return;
        }

        if ($dates['endDate'] < date('Y-m-d')) {
            Application::message('COURSE_EXPIRED', Application::NOTICE);
            $this->setRedirect($referrer);
            return;
        }

        $cpTable = new cpTable();

        if (!$cpTable->load(['courseID' => $courseID, 'participantID' => $participantID]) or !$cpTable->delete()) {
            Application::message('ORGANIZER_STATUS_CHANGE_FAIL', Application::ERROR);
            $this->setRedirect($referrer);
            return;
        }

        if ($instanceIDs = Courses::instanceIDs($courseID)) {
            foreach ($instanceIDs as $instanceID) {
                $ipTable = new ipTable();
                if ($ipTable->load(['instanceID' => $instanceID, 'participantID' => $participantID])) {
                    $ipTable->delete();
                }
            }
        }

        Mailer::registrationUpdate($courseID, $participantID, null);

        Application::message('ORGANIZER_STATUS_CHANGE_SUCCESS');
        $this->setRedirect($referrer);
    }

    /**
     * Registers a participant to a course and related instances.
     * @return void
     */
    public function register(): void
    {
        $this->checkToken();

        $courseID      = Input::id();
        $referrer      = Input::instance()->server->getString('HTTP_REFERER');
        $participantID = User::id();

        if (!cpHelper::validProfile($courseID, $participantID)) {
            Application::message('PROFILE_INCOMPLETE_ERROR', Application::ERROR);
            $this->setRedirect($referrer);
            return;
        }

        $cpData  = ['courseID' => $courseID, 'participantID' => $participantID];
        $cpTable = new cpTable();
        if (!$cpTable->load($cpData)) {
            $cpData['participantDate'] = date('Y-m-d H:i:s');
            $cpData['status']          = cpHelper::ACCEPTED;
            $cpData['statusDate']      = date('Y-m-d H:i:s');
            $cpData['attended']        = 0;
            $cpData['paid']            = 0;

            if (!$cpTable->save($cpData)) {
                Application::message('ORGANIZER_STATUS_CHANGE_FAIL', Application::ERROR);
                $this->setRedirect($referrer);
                return;
            }
        }

        if ($cpTable->status === cpHelper::ACCEPTED) {
            if ($instanceIDs = Courses::instanceIDs($courseID)) {
                foreach ($instanceIDs as $instanceID) {
                    $ipData  = ['instanceID' => $instanceID, 'participantID' => $participantID];
                    $ipTable = new ipTable();
                    if (!$ipTable->load($ipData)) {
                        $ipTable->save($ipData);
                    }
                }
            }
        }

        Mailer::registrationUpdate($courseID, $participantID, $cpTable->status);

        Application::message('ORGANIZER_STATUS_CHANGE_SUCCESS');
        $this->setRedirect($referrer);
    }
}