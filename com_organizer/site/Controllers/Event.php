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
use THM\Organizer\Helpers\Events as Helper;
use THM\Organizer\Tables\EventCoordinators as Coordinator;

/** @inheritDoc */
class Event extends FormController
{
    use Scheduled;

    protected string $list = 'Events';

    /** @inheritDoc */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        // Not editable ergo not in form, this prevents overwrites through table based data preparation.
        unset($data['organizationID']);

        // External references are not in the table and as such won't be automatically prepared.
        $data['coordinatorIDs'] = Input::resourceIDs('coordinatorIDs');

        // Because most values are imported this is the only item that is technically required.
        $this->validate($data, ['code', 'name_de', 'name_en']);

        return $data;
    }

    /** @inheritDoc */
    protected function process(): int
    {
        if ($id = parent::process() and $coordinatorIDs = $this->data['coordinatorIDs']) {
            $existing = Helper::coordinatorIDs($id);

            foreach (array_diff($coordinatorIDs, $existing) as $newID) {
                $coordinator = new Coordinator();
                $coordinator->save(['eventID' => $id, 'personID' => $newID]);
            }

            foreach (array_diff($existing, $coordinatorIDs) as $oldID) {
                $coordinator = new Coordinator();
                $coordinator->load(['eventID' => $id, 'personID' => $oldID]);
                $coordinator->delete();
            }
        }

        return $id;
    }
}