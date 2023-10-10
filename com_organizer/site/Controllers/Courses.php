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
use THM\Organizer\Adapters\Input;
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\OrganizerHelper;
use THM\Organizer\Models;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Courses extends Controller
{
    protected $listView = 'courses';

    protected $resource = 'course';

    /**
     * Prints badges for the selected participants.
     * @return void
     * @throws Exception
     */
    public function badge()
    {
        Input::set('format', 'pdf');
        Input::set('layout', 'Badge');
        parent::display();
    }

    /**
     * De-/registers a participant from/to a course.
     * @return void
     */
    public function deregister()
    {
        $referrer = Input::getInput()->server->getString('HTTP_REFERER');

        $model = new Models\Course();

        if ($model->deregister()) {
            OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_SUCCESS', 'success');
        } else {
            OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_FAIL', 'error');
        }

        $this->setRedirect($referrer);
    }

    /**
     * Makes call to the model's import function, and redirects to the manager view if the file .
     * @return void
     */
    public function import()
    {
        $url  = Helpers\Routing::getRedirectBase();
        $view = 'Courses';

        if (JDEBUG) {
            OrganizerHelper::message('ORGANIZER_DEBUG_ON', 'error');
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
            } else {
                $view = 'CoursesImport';
                OrganizerHelper::message('ORGANIZER_FILE_ENCODING_INVALID', 'error');
            }
        } else {
            $view = 'CoursesImport';
            OrganizerHelper::message('ORGANIZER_FILE_TYPE_NOT_ALLOWED', 'error');
        }

        $url .= "&view=$view";
        $this->setRedirect($url);
    }

    /**
     * Opens the course participants view for the selected course.
     * @return void
     * @throws Exception
     */
    public function participants()
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
    public function register()
    {
        $courseID      = Input::getID();
        $referrer      = Input::getInput()->server->getString('HTTP_REFERER');
        $participantID = Helpers\Users::getID();

        if (!Helpers\CourseParticipants::validProfile($courseID, $participantID)) {
            OrganizerHelper::message('ORGANIZER_PROFILE_INCOMPLETE_ERROR', 'error');
        } else {
            $model = new Models\Course();

            if ($model->register()) {
                OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_SUCCESS', 'success');
            } else {
                OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_FAIL', 'error');
            }
        }


        $this->setRedirect($referrer);
    }
}
