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

use THM\Organizer\Adapters\Application;
use THM\Organizer\Helpers;
use THM\Organizer\Tables;

/**
 * Class loads a form for editing event data.
 */
class EventEdit extends EditModel
{
    /**
     * Checks access to edit the resource.
     * @return void
     */
    public function authorize()
    {
        if (!Helpers\Users::getID()) {
            Application::error(401);
        }

        if (!Helpers\Can::edit('events', (int) $this->item->id)) {
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
     * @return Tables\Events A Table object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTable($name = '', $prefix = '', $options = []): Tables\Events
    {
        return new Tables\Events();
    }
}
