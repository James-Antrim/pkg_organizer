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

use Joomla\CMS\Router\Route;
use THM\Organizer\Adapters\Application;
use THM\Organizer\Helpers;
use THM\Organizer\Models;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Groups extends ListController
{
    use Activated;

    protected string $item = 'Group';

    /**
     * Makes call to the model's batch function, and redirects to the manager view.
     * @return void
     */
    public function batch(): void
    {
        $model = new Models\Group();

        if ($model->batch()) {
            Application::message('ORGANIZER_SAVE_SUCCESS');
        }
        else {
            Application::message('ORGANIZER_SAVE_FAIL', Application::ERROR);
        }

        $url = Helpers\Routing::getRedirectBase() . '&view=' . Application::getClass($this);
        $this->setRedirect($url);
    }

    /**
     * Sets the publication status for any group / complete term pairing to true
     * @return void
     */
    public function publishPast(): void
    {
        $group = new Models\Group();

        if ($group->publishPast()) {
            Application::message('ORGANIZER_SAVE_SUCCESS');
        }
        else {
            Application::message('ORGANIZER_SAVE_FAIL', Application::ERROR);
        }

        $url = Helpers\Routing::getRedirectBase() . '&view=groups';
        $this->setRedirect(Route::_($url, false));
    }
}
