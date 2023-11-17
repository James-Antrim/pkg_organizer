<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers;
use THM\Organizer\Tables;

/**
 * Class which manages stored building data.
 */
class CleaningGroup extends BaseModel
{
    /**
     * Authorizes the user.
     * @return void
     */
    protected function authorize()
    {
        if (!Helpers\Can::manage('facilities')) {
            Application::error(403);
        }
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return Tables\CleaningGroups  A Table object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTable($name = '', $prefix = '', $options = []): Tables\CleaningGroups
    {
        return new Tables\CleaningGroups();
    }

    /**
     * Toggles the monitor's use of default settings
     * @return bool  true on success, otherwise false
     */
    public function toggle(): bool
    {
        $this->authorize();

        $groupID = Input::getID();
        $group   = new Tables\CleaningGroups();
        if (!$groupID or !$group->load($groupID)) {
            return false;
        }

        $newValue = !$group->relevant;
        $group->set('relevant', $newValue);

        return $group->store();
    }
}
