<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2024 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer\Controllers;

use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers\{Can, Documentable, Organizations};

/** @inheritDoc */
abstract class CurriculumResources extends ListController
{
    /** @inheritDoc */
    protected function authorize(): void
    {
        if (Can::administrate()) {
            return;
        }

        if (!Organizations::documentableIDs()) {
            Application::error(403);
        }
    }

    /**
     * Authorizes the import of specific curriculum resources.
     * @return void
     */
    protected function authorizeImport(): void
    {
        if (Can::administrate()) {
            return;
        }

        $this->checkToken();

        $authorized  = false;
        $selectedIDs = Input::selectedIDs();
        if ($selectedIDs) {
            /** @var Documentable $helper */
            $helper        = "THM\\Organizer\\Helpers\\" . Application::uqClass($this);
            $authorizedIDs = $helper::documentableIDs();
            if (!array_diff($selectedIDs, $authorizedIDs)) {
                $authorized = true;
            }
        }

        if (!$authorized) {
            Application::error('403', Application::ERROR);
        }
    }

    /** @inheritDoc */
    public function delete(): void
    {
        $this->checkToken();
        $this->authorize();

        if (!$selectedIDs = Input::selectedIDs()) {
            Application::message('LIST_SELECTION_WARNING', Application::WARNING);

            return;
        }

        /** @var Documentable $helper */
        $helper     = "THM\\Organizer\\Helpers\\" . Application::uqClass($this);
        $controller = "THM\\Organizer\\Controllers\\" . $this->item;
        $deleted    = 0;
        $selected   = count($selectedIDs);

        /** @var CurriculumResource $controller */
        $controller = new $controller();
        foreach ($selectedIDs as $selectedID) {
            if (!$helper::documentable($selectedID)) {
                Application::error(403);
            }

            if ($controller->delete($selectedID)) {
                $deleted++;
            }
        }

        $this->farewell($selected, $deleted, true);
    }
}