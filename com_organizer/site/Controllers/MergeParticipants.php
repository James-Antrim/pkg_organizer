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

use THM\Organizer\Adapters\Database as DB;
use THM\Organizer\Adapters\Input;

/**
 * @inheritDoc
 */
class MergeParticipants extends MergeController
{
    use Unscheduled;

    protected string $list = 'Participants';
    protected string $mergeContext = 'participant';

    /**
     * Resolves the incoming ids for later ease of use.
     * @return void
     */
    protected function resolveIDs(): void
    {
        if (!$domain = Input::getParams()->get('emailFilter')) {
            parent::resolveIDs();
            return;
        }

        $mergeIDs = Input::getIntCollection('ids');
        asort($mergeIDs);

        $query = DB::getQuery();
        $query->select(DB::qn(['id', 'email']))->from(DB::qn('#__users'))
            ->whereIn(DB::qn('id'), $mergeIDs)
            ->order(DB::qn('id'));
        DB::setQuery($query);

        if ($results = DB::loadAssocList('id') and count($results) === count($mergeIDs)) {
            $this->mergeIDs = $mergeIDs;

            foreach ($mergeIDs as $index => $mergeID) {
                if (strpos($results[$mergeID]['email'], $domain)) {
                    $this->mergeID = $mergeID;
                    unset($mergeIDs[$index]);
                    break;
                }
            }

            $this->deprecatedIDs = $mergeIDs;

            return;
        }

        parent::resolveIDs();
    }

    private function courseParticipation(): bool
    {

    }

    private function instanceParticipation(): bool
    {

    }

    protected function updateReferences(): bool
    {
        // course participants
        // instance participants
        // users references???
        return false;
    }
}