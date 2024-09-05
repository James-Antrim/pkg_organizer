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
use THM\Organizer\Tables\Instances as Table;

/**
 * Standard implementation for updating instance participation numbers.
 */
trait Participation
{

    /**
     * Updates participation numbers for a single instance.
     *
     * @param   int  $instanceID
     *
     * @return bool
     */
    private function updateParticipation(int $instanceID): bool
    {
        $query = DB::getQuery();
        $query->select('*')->from(DB::qn('#__organizer_instance_participants'))->where("instanceID = $instanceID");
        DB::setQuery($query);

        if (!$results = DB::loadAssocList()) {
            return false;
        }

        $attended   = 0;
        $bookmarked = 0;
        $registered = 0;

        foreach ($results as $result) {
            $bookmarked++;
            $attended   = $attended + $result['attended'];
            $registered = $registered + $result['registered'];
        }

        $table = new Table();
        $table->load($instanceID);

        $updated = false;

        if ($attended and $attended !== $table->attended) {
            $table->attended = $attended;
            $updated         = true;
        }

        if ($bookmarked and $bookmarked !== $table->bookmarked) {
            $table->bookmarked = $bookmarked;
            $updated           = true;
        }

        if ($registered and $registered !== $table->registered) {
            $table->registered = $registered;
            $updated           = true;
        }

        $table->store();

        return $updated;
    }
}