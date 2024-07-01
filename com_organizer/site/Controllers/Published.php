<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2024 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use THM\Organizer\Adapters\Application;
use THM\Organizer\Helpers\{Groups as Helper, Terms};
use THM\Organizer\Tables\GroupPublishing as Table;

trait Published
{
    /**
     * Sets publishing values for groups in term contexts. Also performs authorization checks on individual groups in the process.
     * Function used outside FormController context, so parameters are required.
     *
     * @param   array|int  $groupIDs    the id or ids of the groups to set the publishing values of
     * @param   array      $publishing  the publishing values set in a form
     *
     * @return int the number of groups updated without issue
     */
    private function savePublishing(array|int $groupIDs, array $publishing = []): int
    {
        $groupIDs = is_int($groupIDs) ? [$groupIDs] : $groupIDs;
        $now      = date('Y-m-d H:i:s');
        $termIDs  = Terms::getIDs();
        $updated  = 0;

        foreach ($groupIDs as $groupID) {
            if (!Helper::schedulable($groupID)) {
                Application::error(403);
            }

            $copacetic = true;
            foreach ($termIDs as $termID) {
                $data    = ['groupID' => $groupID, 'termID' => $termID];
                $expired = Terms::endDate($termID) < $now;
                $table   = new Table();
                $table->load($data);

                if ($expired or !isset($publishing[$termID])) {
                    $data['published'] = Helper::PUBLISHED;
                }
                else {
                    $data['published'] = empty($publishing[$termID]) ? Helper::UNPUBLISHED : Helper::PUBLISHED;
                }

                if (!$table->save($data)) {
                    $copacetic = false;
                }
            }

            if ($copacetic) {
                $updated++;
            }
        }

        return $updated;
    }
}