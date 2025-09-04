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

use THM\Organizer\Adapters\Input;

/**
 * Standard implementation for de-/activating resources.
 */
trait Activated
{
    /**
     * Activates selected resources.
     * @return void
     */
    public function activate(): void
    {
        $this->checkToken();
        $this->authorize();

        $selectedIDs = Input::selectedIDs();
        $selected    = count($selectedIDs);
        $updated     = $this->updateBool('active', $selectedIDs, true);
        $this->farewell($selected, $updated);
    }

    /**
     * De-activates selected resources.
     * @return void
     */
    public function deactivate(): void
    {
        $this->checkToken();
        $this->authorize();

        $selectedIDs = Input::selectedIDs();
        $selected    = count($selectedIDs);
        $updated     = $this->updateBool('active', $selectedIDs, false);
        $this->farewell($selected, $updated);
    }
}