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

/**
 * @inheritDoc
 */
class FieldColor extends FormController
{
    protected string $list = 'FieldColors';

    /*protected function process(): int
    {
        $this->checkToken();
        $this->authorize();

        $id   = Input::getID();
        $data = $this->prepareData();

        // For save to copy, will otherwise be identical.
        $data['id'] = $id;
        $table      = $this->getTable();

        return $this->store($table, $data, $id);
    }*/

    /**
     * @inheritDoc
     */
    /*public function save(array $data = [])
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
    }**/
}