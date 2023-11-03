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

use THM\Organizer\Adapters\Application;
use THM\Organizer\Helpers;
use THM\Organizer\Models;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Schedules extends ListController
{
    protected string $listView = 'schedules';

    protected string $item = 'Schedule';

    /**
     * Notifies the points of contact for affected organizations of changes made to the schedule.
     * @return void
     */
    /*public function notify()
    {
        $model = new Models\Schedule();

        if ($model->notify())
        {
            Application::message('NOTIFY_SUCCESS');
        }
        else
        {
            Application::message('NOTIFY_FAIL', Application::ERROR);
        }

        $url = Helpers\Routing::getRedirectBase();
        $url .= "&view=schedules";
        $this->setRedirect($url);
    }*/

    /**
     * Rebuilds the delta status of planning resources and relations.
     * @return void
     */
    public function rebuild(): void
    {
        $model = new Models\Schedule();

        if ($model->rebuild()) {
            Application::message('REBUILD_SUCCESS');
        }
        else {
            Application::message('REBUILD_FAIL', Application::ERROR);
        }

        $url = Helpers\Routing::getRedirectBase();
        $url .= "&view=schedules";
        $this->setRedirect($url);
    }

    /**
     * Uses the model's reference function to set the marked schedule as the reference in organization/term context.
     * @return void
     */
    public function reference(): void
    {
        $model = new Models\Schedule();

        if ($model->reference()) {
            Application::message('REFERENCE_SUCCESS');
        }
        else {
            Application::message('REFERENCE_FAIL', Application::ERROR);
        }

        $url = Helpers\Routing::getRedirectBase();
        $url .= "&view=schedules";
        $this->setRedirect($url);
    }

    /**
     * Uses the model's upload function to validate and save the file to the database should validation be successful.
     * @return void
     */
    public function upload(): void
    {
        $url = Helpers\Routing::getRedirectBase();
        if (JDEBUG) {
            Application::message('DEBUG_ON', Application::ERROR);
            $url .= "&view=Schedules";
            $this->setRedirect($url);

            return;
        }

        $form      = $this->input->files->get('jform', [], '[]');
        $file      = $form['file'];
        $validType = (!empty($file['type']) and $file['type'] == 'text/xml');

        if ($validType) {
            if (mb_detect_encoding($file['tmp_name'], 'UTF-8', true) === 'UTF-8') {
                $model = new Models\Schedule();
                $view  = $model->upload() ? 'Schedules' : 'Schedule_Edit';
            }
            else {
                $view = 'Schedule_Edit';
                Application::message('FILE_ENCODING_INVALID', Application::ERROR);
            }
        }
        else {
            $view = 'Schedule_Edit';
            Application::message('FILE_TYPE_NOT_ALLOWED', Application::ERROR);
        }

        $url .= "&view=$view";
        $this->setRedirect($url);
    }
}
