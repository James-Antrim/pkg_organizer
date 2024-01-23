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
class Programs extends CurriculumResources
{
    use Activated;

    protected string $item = 'Program';

    /**
     * Makes call to the model's update batch function, and redirects to the manager view.
     * @return void
     */
    public function update(): void
    {
        $model = new Models\Program();

        if ($model->update()) {
            Application::message('ORGANIZER_UPDATE_SUCCESS');
        }
        else {
            Application::message('ORGANIZER_UPDATE_FAIL', Application::ERROR);
        }

        $url = Helpers\Routing::getRedirectBase() . '&view=' . Application::getClass($this);
        $this->setRedirect($url);
    }
}
