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

use THM\Organizer\Tables\Campuses as Table;

/** @inheritDoc */
class Campus extends FormController
{
    use FluMoxed;

    protected string $list = 'Campuses';

    /** @inheritDoc */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        $this->validate($data, ['name_de', 'name_en']);

        if (!empty($data['parentID'])) {
            /** @var Table $table */
            $table = $this->getTable();

            // Referenced parent doesn't exist or is itself a subordinate campus.
            if (!$table->load($data['parentID']) or !empty($table->parentID)) {
                $data['parentID'] = null;
            }
        }

        return $data;
    }
}