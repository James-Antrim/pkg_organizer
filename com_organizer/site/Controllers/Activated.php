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

trait Activated
{
    /**
     * Activates resources.
     * @return void
     */
    public function activate()
    {
        $fqName = 'THM\\Organizer\\Models\\' . ucfirst($this->resource);
        $model  = new $fqName();

        if ($model->activate()) {
            Application::message('ORGANIZER_DEACTIVATION_SUCCESS');
        }
        else {
            Application::message('ORGANIZER_DEACTIVATION_FAIL', Application::ERROR);
        }

        $url = Helpers\Routing::getRedirectBase() . '&view=' . $this->listView;
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Deactivates resources.
     * @return void
     */
    public function deactivate()
    {
        $fqName = 'THM\\Organizer\\Models\\' . ucfirst($this->resource);
        $model  = new $fqName();

        if ($model->deactivate()) {
            Application::message('ORGANIZER_DEACTIVATION_SUCCESS');
        }
        else {
            Application::message('ORGANIZER_DEACTIVATION_FAIL', Application::ERROR);
        }

        $url = Helpers\Routing::getRedirectBase() . '&view=' . $this->listView;
        $this->setRedirect(Route::_($url, false));
    }
}