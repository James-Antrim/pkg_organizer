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

/** @inheritDoc */
class Monitors extends ListController
{
    use FacilityManageable;

    protected string $item = 'Monitor';

    /**
     * Activates selected resources.
     * @return void
     */
    public function individualize(): void
    {
        $this->checkToken();
        $this->authorize();

        $selectedIDs = Input::getSelectedIDs();
        $selected    = count($selectedIDs);
        $updated     = $this->updateBool('useDefaults', $selectedIDs, false);
        $this->farewell($selected, $updated);
    }

    /**
     * De-activates selected resources.
     * @return void
     */
    public function useDefaults(): void
    {
        $this->checkToken();
        $this->authorize();

        $selectedIDs = Input::getSelectedIDs();
        $selected    = count($selectedIDs);
        $updated     = $this->updateBool('useDefaults', $selectedIDs, true);
        $this->farewell($selected, $updated);
    }
}
