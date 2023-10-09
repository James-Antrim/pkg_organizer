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
use THM\Organizer\Controller;
use THM\Organizer\Helpers;
use THM\Organizer\Models;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Organizer extends Controller
{
    /**
     * Removes unused bookings and deprecated participation data.
     * @return void
     */
    public function cleanBookings()
    {
        $model = new Models\Schedule();
        $model->cleanBookings(true);
        $url = Helpers\Routing::getRedirectBase() . "&view=organizer";
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Creates a new booking element for a given instance and redirects to the corresponding instance participants view.
     * @return void
     */
    public function cleanDB()
    {
        $model = new Models\Organizer();
        $model->cleanDB();
        $url = Helpers\Routing::getRedirectBase() . "&view=organizer";
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Creates a new booking element for a given instance and redirects to the corresponding instance participants view.
     * @return void
     */
    public function reKeyTables()
    {
        $model = new Models\Organizer();
        $model->reKeyTables();
        $url = Helpers\Routing::getRedirectBase() . "&view=organizer";
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Updates all instance participation numbers.
     * @return void
     */
    public function updateNumbers()
    {
        $model = new Models\Instance();
        $model->updateNumbers();
        $url = Helpers\Routing::getRedirectBase() . "&view=organizer";
        $this->setRedirect(Route::_($url, false));
    }
}
