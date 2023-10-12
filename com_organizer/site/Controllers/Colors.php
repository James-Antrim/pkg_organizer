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
class Colors extends Controller
{
    protected $listView = 'colors';

    protected $resource = 'color';

    /**
     * Save form data to the database.
     * @return void
     */
    public function save()
    {
        $model = new Models\Color();
        $url   = Helpers\Routing::getRedirectBase() . '&view=';
        $url   .= Helpers\Can::administrate() ? 'colors' : 'field_colors';

        if ($model->save()) {
            Application::message('ORGANIZER_SAVE_SUCCESS');
        } else {
            Application::message('ORGANIZER_SAVE_FAIL', Application::ERROR);
        }

        $this->setRedirect(Route::_($url, false));
    }
}
