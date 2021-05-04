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
 * Class which manages stored field (of expertise) data.
 */
class FieldColor extends BaseModel
{
    /**
     * Authorizes the user.
     *
     * @return void
     */
    protected function authorize()
    {
        if ($organizationID = Helpers\Input::getInt('organizationID')
            and Helpers\Can::document('organization', $organizationID)
        ) {
            return;
        }

        if ($fcID = Helpers\Input::getID() and Helpers\Can::document('fieldcolor', $fcID)) {
            return;
        }

        Helpers\OrganizerHelper::error(403);
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return Tables\FieldColors A Table object
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTable($name = '', $prefix = '', $options = [])
    {
        return new Tables\FieldColors;
    }

    /**
     * Attempts to save the resource.
     *
     * @param   array  $data  the data from the form
     *
     * @return mixed int id of the resource on success, otherwise bool false
     */
    public function save($data = [])
    {
        $this->authorize();

        $data  = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;
        $table = $this->getTable();

        if (empty($data['id'])) {
            return $table->save($data) ? $table->id : false;
        }

        $table->load($data['id']);
        $table->colorID = $data['colorID'];

        return $table->store();
    }
}
