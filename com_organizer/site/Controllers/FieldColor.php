<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use THM\Organizer\Adapters\Input;
use THM\Organizer\Tables\FieldColors as Table;

/**
 * @inheritDoc
 */
class FieldColor extends FormController
{
    protected string $list = 'FieldColors';

    /**
     * @inheritDoc
     */
    protected function process(): int
    {
        $this->checkToken();
        $this->authorize();

        $id   = Input::getID();
        $data = $this->prepareData();

        /** @var Table $table */
        $table = $this->getTable();

        // Pull accurate disabled field values from stored table values.
        if (!empty($id)) {
            $table->load($id);
            $data['fieldID']        = $table->fieldID;
            $data['organizationID'] = $table->organizationID;
        }

        return $this->store($table, $data, $id);
    }

    /**
     * Validates the form data beyond the implicit type validation performed during prepareData.
     *
     * @param   array  $data      the form data to validate
     * @param   array  $required  the required fields
     *
     * @return void
     */
    protected function validate(array &$data, array $required = []): void
    {
        if (empty($data['id'])) {
            parent::validate($data, ['colorID', 'fieldID', 'organizationID']);
            return;
        }

        //The other fields are disabled and deliver ~empty values.
        parent::validate($data, ['colorID']);
    }
}