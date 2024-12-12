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

use THM\Organizer\Tables\{CleaningGroups, RoomKeys};
use THM\Organizer\Adapters\Application;
use THM\Organizer\Adapters\Input;

/** @inheritDoc */
class RoomKey extends FormController
{
    use FacilityManageable;

    protected string $list = 'RoomKeys';

    /** @inheritDoc */
    protected function process(): int
    {
        $this->checkToken();
        $this->authorize();

        $data = Input::getFormItems();

        /** @var RoomKeys $table */
        $table = $this->getTable();

        //The only thing modifiable is the cleaning group assignment.
        if (empty($data['id']) or !$table->load($data['id'])) {
            Application::message('NOT_SAVED');
            return 0;
        }

        $cleaningGroup     = new CleaningGroups();
        $cleaningID        = (int) $data['cleaningID'];
        $table->cleaningID = $cleaningGroup->load($cleaningID) ? $cleaningID : null;

        if ($table->store()) {
            Application::message('SAVED');
            return $table->id;
        }
        else {
            Application::message('NOT_SAVED');
            return 0;
        }
    }
}