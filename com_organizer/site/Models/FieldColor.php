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
 * Class which manages stored field (of expertise) data.
 */
class FieldColor extends BaseModel
{
    /**
     * Authorizes the user.
     * @return void
     */
    protected function authorize()
    {
        if ($organizationID = Input::getInt('organizationID')
            and Helpers\Can::document('organization', $organizationID)
        ) {
            return;
        }

        if ($fcID = Input::getID() and Helpers\Can::document('fieldcolor', $fcID)) {
            return;
        }

        Application::error(403);
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param string $name    The table name. Optional.
     * @param string $prefix  The class prefix. Optional.
     * @param array  $options Configuration array for model. Optional.
     *
     * @return Tables\FieldColors A Table object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTable($name = '', $prefix = '', $options = []): Tables\FieldColors
    {
        return new Tables\FieldColors();
    }

    /**
     * @inheritDoc
     */
    public function save(array $data = [])
    {
        $this->authorize();

        $data  = empty($data) ? Input::getFormItems()->toArray() : $data;
        $table = $this->getTable();

        if (empty($data['id'])) {
            return $table->save($data) ? $table->id : false;
        }

        $table->load($data['id']);
        $table->colorID = $data['colorID'];

        return $table->store();
    }
}
