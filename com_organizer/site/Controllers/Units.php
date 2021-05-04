<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Controllers;

use Organizer\Controller;
use Organizer\Helpers;
use Organizer\Models\Unit;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Units extends Controller
{
    protected $listView = 'units';

    protected $resource = 'unit';

    /**
     * Creates a course entry based on the data associated with a unit.
     *
     * @return void
     */
    public function addCourse()
    {
        $model = new Unit();

        if ($resourceID = $model->addCourse()) {
            Helpers\OrganizerHelper::message('ORGANIZER_SAVE_SUCCESS');

            $url = Helpers\Routing::getRedirectBase() . "&view=course_edit&id=$resourceID";
        } else {
            Helpers\OrganizerHelper::message('ORGANIZER_SAVE_FAIL', 'error');

            $url = Helpers\Routing::getRedirectBase() . "&view=units";
        }

        $this->setRedirect($url);
    }
}
