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

/**
 * @inheritDoc
 */
class Run extends FormController
{
    protected string $list = 'Runs';

    /**
     * Attempts to save the resource.
     *
     * @param   array  $data  the data from the form
     *
     * @return int|bool int id of the resource on success, otherwise bool false
     */
    /*public function save(array $data = [])
    {
        $this->authorize();

        $data    = empty($data) ? Input::getFormItems()->toArray() : $data;
        $endDate = '';
        $index   = 1;
        $runs    = [];

        foreach ($data['run'] as $row) {
            $endDate      = $endDate < $row['endDate'] ? $row['endDate'] : $endDate;
            $runs[$index] = $row;
            ++$index;
        }

        $data['endDate'] = $endDate;
        $run             = ['runs' => $runs];
        $data['run']     = json_encode($run, JSON_UNESCAPED_UNICODE);

        $table = new Table();

        return $table->save($data) ? $table->id : false;
    }*/
}
