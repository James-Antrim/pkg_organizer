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

use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers;
use THM\Organizer\Models\Participant;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Participants extends Controller
{
    protected $listView = 'participants';

    protected $resource = 'participant';

    /**
     * Attempts to automatically merge entries according to criteria for unique identification.
     * @return void
     */
    public function automaticMerge()
    {
        $model = new Participant();
        $model->automaticMerge();
        $url = Helpers\Routing::getRedirectBase();
        $url .= "&view=$this->listView";
        $this->setRedirect($url);
    }

    /**
     * Save user information from form and if course id defined sign in or out of course
     * then redirect to course list view
     * @return void
     */
    public function save()
    {
        $model = new Participant();

        if ($model->save()) {
            Application::message('ORGANIZER_SAVE_SUCCESS');
        } else {
            Application::message('ORGANIZER_SAVE_FAIL', Application::ERROR);
        }

        $this->setRedirect(Input::getString('referrer'));
    }
}
