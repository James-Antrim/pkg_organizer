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
use THM\Organizer\Models\Unit;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Units extends Controller
{
    protected $listView = 'units';

    protected $resource = 'unit';

    /**
     * Creates a course entry based on the data associated with a unit.
     * @return void
     */
    public function addCourse()
    {
        $model = new Unit();

        if ($resourceID = $model->addCourse()) {
            Application::message('ORGANIZER_SAVE_SUCCESS');

            $url = Helpers\Routing::getRedirectBase() . "&view=course_edit&id=$resourceID";
        } else {
            Application::message('ORGANIZER_SAVE_FAIL', Application::ERROR);

            $url = Helpers\Routing::getRedirectBase() . "&view=units";
        }

        $this->setRedirect($url);
    }
}
