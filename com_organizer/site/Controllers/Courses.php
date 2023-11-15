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
use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers;
use THM\Organizer\Models;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Courses extends ListController
{
    protected string $item = 'Course';

    /**
     * Prints badges for the selected participants.
     * @return void
     * @throws Exception
     */
    public function badge(): void
    {
        Input::set('format', 'pdf');
        Input::set('layout', 'Badge');
        parent::display();
    }

    /**
     * De-/registers a participant from/to a course.
     * @return void
     */
    public function deregister(): void
    {
        $referrer = Input::getInput()->server->getString('HTTP_REFERER');

        $model = new Models\Course();

        if ($model->deregister()) {
            Application::message('ORGANIZER_STATUS_CHANGE_SUCCESS');
        }
        else {
            Application::message('ORGANIZER_STATUS_CHANGE_FAIL', Application::ERROR);
        }

        $this->setRedirect($referrer);
    }

    /**
     * Makes call to the model's import function, and redirects to the manager view if the file .
     * @return void
     */
    public function import(): void
    {
        $url  = Helpers\Routing::getRedirectBase();
        $view = 'Courses';

        if (JDEBUG) {
            Application::message('ORGANIZER_DEBUG_ON', Application::ERROR);
            $url .= "&view=$view";
            $this->setRedirect($url);

            return;
        }

        $form = $this->input->files->get('jform', [], '[]');
        $file = $form['file'];

        if (!empty($file['type']) and $file['type'] === 'text/plain') {
            if (mb_detect_encoding($file['tmp_name'], 'UTF-8', true) === 'UTF-8') {
                $model = new Models\Course();
                $view  = $model->import() ? 'Courses' : 'CoursesImport';
            }
            else {
                $view = 'CoursesImport';
                Application::message('ORGANIZER_FILE_ENCODING_INVALID', Application::ERROR);
            }
        }
        else {
            $view = 'CoursesImport';
            Application::message('ORGANIZER_FILE_TYPE_NOT_ALLOWED', Application::ERROR);
        }

        $url .= "&view=$view";
        $this->setRedirect($url);
    }

    /**
     * Opens the course participants view for the selected course.
     * @return void
     * @throws Exception
     */
    public function participants(): void
    {
        if (!$courseID = Input::getSelectedIDs()[0]) {
            parent::display();

            return;
        }

        $this->setRedirect(Uri::base() . "?option=com_organizer&view=course_participants&id=$courseID");
    }

    /**
     * De-/registers a participant from/to a course.
     * @return void
     */
    public function register(): void
    {
        $courseID      = Input::getID();
        $referrer      = Input::getInput()->server->getString('HTTP_REFERER');
        $participantID = Helpers\Users::getID();

        if (!Helpers\CourseParticipants::validProfile($courseID, $participantID)) {
            Application::message('ORGANIZER_PROFILE_INCOMPLETE_ERROR', Application::ERROR);
        }
        else {
            $model = new Models\Course();

            if ($model->register()) {
                Application::message('ORGANIZER_STATUS_CHANGE_SUCCESS');
            }
            else {
                Application::message('ORGANIZER_STATUS_CHANGE_FAIL', Application::ERROR);
            }
        }


        $this->setRedirect($referrer);
    }
}
