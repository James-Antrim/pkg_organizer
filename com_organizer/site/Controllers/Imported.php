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
use THM\Organizer\Helpers\OrganizerHelper;

/**
 * Class receives user actions and performs access checks and redirection.
 */
trait Imported
{
    /**
     * Makes call to the models's save and importSingle functions, and redirects to the same view.
     * @return void
     */
    public function applyImport()
    {
        $modelName = 'THM\\Organizer\\Models\\' . OrganizerHelper::getClass($this->resource);
        $model     = new $modelName();

        if ($resourceID = $model->save() and $model->importSingle($resourceID)) {
            Application::message('ORGANIZER_SAVE_SUCCESS');
        } else {
            Application::message('ORGANIZER_SAVE_FAIL', Application::ERROR);
        }

        $url = Helpers\Routing::getRedirectBase() . "&view={$this->resource}_edit&id=$resourceID";
        $this->setRedirect($url);
    }

    /**
     * Makes call to the models's import batch function, and redirects to the manager view.
     * @return void
     */
    public function import()
    {
        $modelName = 'THM\\Organizer\\Models\\' . OrganizerHelper::getClass($this->resource);
        $model     = new $modelName();

        if ($model->import()) {
            Application::message('ORGANIZER_IMPORT_SUCCESS');
        } else {
            Application::message('ORGANIZER_IMPORT_FAIL', Application::ERROR);
        }

        $url = Helpers\Routing::getRedirectBase();
        $url .= "&view=$this->listView";
        $this->setRedirect($url);
    }

    /**
     * Save form data to the database.
     * @return void
     */
    public function saveImport()
    {
        $modelName = 'THM\\Organizer\\Models\\' . OrganizerHelper::getClass($this->resource);
        $model     = new $modelName();
        $url       = Helpers\Routing::getRedirectBase() . "&view=$this->listView";

        if ($resourceID = $model->save() and $model->importSingle($resourceID)) {
            Application::message('ORGANIZER_SAVE_SUCCESS');
        } else {
            Application::message('ORGANIZER_SAVE_FAIL', Application::ERROR);
        }

        $this->setRedirect(Route::_($url, false));
    }
}
