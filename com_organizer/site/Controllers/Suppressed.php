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
trait Suppressed
{
    /**
     * Reveals the selected resources. Display would have caused a name conflict. :(
     * @return void
     */
    public function reveal(): void
    {
        $this->checkToken();
        $this->authorize();

        $selectedIDs = Input::getSelectedIDs();
        $selected    = count($selectedIDs);
        $updated     = $this->updateBool('suppress', $selectedIDs, false);
        $this->farewell($selected, $updated);
    }

    /**
     * Suppresses the selected resources.
     * @return void
     */
    public function suppress(): void
    {
        $this->checkToken();
        $this->authorize();

        $selectedIDs = Input::getSelectedIDs();
        $selected    = count($selectedIDs);
        $updated     = $this->updateBool('suppress', $selectedIDs, true);
        $this->farewell($selected, $updated);
    }
}