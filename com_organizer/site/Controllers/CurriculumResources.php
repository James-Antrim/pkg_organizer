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
use THM\Organizer\Helpers\{Documentable, Organizations};

/**
 * Class performs access checks, user actions and redirection for listed curriculum resources.
 */
abstract class CurriculumResources extends ListController
{
    use Ranges;

    /**
     * Authorization check multiple curriculum resources. Individual resource authorization is later checked as appropriate.
     * @return void
     */
    protected function authorize(): void
    {
        if (!Organizations::documentableIDs()) {
            Application::error(403);
        }
    }

    /**
     * Deletes selected curriculum resources and their subordinate resources as appropriate.
     * @return void
     */
    public function delete(): void
    {
        $this->checkToken();
        $this->authorize();

        if (!$selectedIDs = Input::getSelectedIDs()) {
            Application::message('NO_SELECTION', Application::WARNING);

            return;
        }

        /** @var Documentable $helper */
        $helper     = "THM\\Organizer\\Helpers\\" . Application::getClass(get_called_class());
        $controller = $this->item;
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

    /**
     * Method to import data associated with selected curriculum resources.
     * @return void
     */
    public function import(): void
    {
        if (Application::getClass($this) === 'Pools') {
            Application::error(501);
        }

        $this->checkToken();
        $this->authorize();

        if (!$selectedIDs = Input::getSelectedIDs()) {
            Application::message('NO_SELECTION', Application::WARNING);

            return;
        }

        $controller = $this->item;
        $imported   = 0;
        $selected   = count($selectedIDs);

        /** @var CurriculumResource $controller */
        $controller = new $controller();
        foreach ($selectedIDs as $selectedID) {
            if ($controller->import($selectedID)) {
                $imported++;
            }
        }

        $this->farewell($selected, $imported);
    }
}