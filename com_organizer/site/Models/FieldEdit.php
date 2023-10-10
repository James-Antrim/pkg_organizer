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
 * Class loads a form for editing field (of expertise) data.
 */
class FieldEdit extends EditModel
{
    /**
     * Checks access to edit the resource.
     * @return void
     */
    public function authorize()
    {
        if (!Helpers\Can::documentTheseOrganizations()) {
            Application::error(403);
        }
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param string $name    The table name. Optional.
     * @param string $prefix  The class prefix. Optional.
     * @param array  $options Configuration array for model. Optional.
     *
     * @return Tables\Fields A Table object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTable($name = '', $prefix = '', $options = []): Tables\Fields
    {
        return new Tables\Fields();
    }
}
