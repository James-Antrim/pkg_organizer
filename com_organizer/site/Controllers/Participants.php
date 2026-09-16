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

use THM\Organizer\Adapters\Database as DB;
use THM\Organizer\Helpers\Instances;

/** @inheritDoc */
class Participants extends ListController
{
    /**
     * Updates all instance participation numbers.
     * @return void
     */
    public function update(): void
    {
        $this->checkToken();
        $this->authorize();

        $query = DB::query();
        $query->select('DISTINCT ' . DB::qn('instanceID'))->from(DB::qn('#__organizer_instance_participants'));
        DB::set($query);

        $instanceIDs = DB::integers();
        $relevant    = count($instanceIDs);
        $updated     = 0;

        foreach ($instanceIDs as $instanceID) {
            if (Instances::updateNumbers($instanceID)) {
                $updated++;
            }
        }

        $this->farewell($relevant, $updated);
    }
}
