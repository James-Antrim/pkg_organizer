<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored room type data.
 */
class Roomtype extends BaseModel
{
    /**
     * Authorizes the user.
     *
     * @return void
     */
    protected function authorize()
    {
        if (!Helpers\Can::manage('facilities')) {
            Helpers\OrganizerHelper::error(403);
        }
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return Tables\Roomtypes A Table object
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTable($name = '', $prefix = '', $options = [])
    {
        return new Tables\Roomtypes;
    }
}
