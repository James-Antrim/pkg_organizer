<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use Joomla\Database\DatabaseQuery;
use THM\Organizer\Adapters\Database as DB;
use THM\Organizer\Helpers\{Categories, Terms};
use THM\Organizer\Views\HTML\Statistics as View;

/**
 * Class calculates lesson statistics and loads them into the view context.
 */
class Statistics extends ListModel
{
    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        $state  = $this->state;
        $termID = (int) $state->get('list.termID');

        $query = DB::getQuery();
        $query->select(['DISTINCT ' . DB::qn('i.id', 'instanceID'), DB::qn('i') . '.*', DB::qn('b.date')])
            ->from(DB::qn('#__organizer_instances', 'i'))
            ->innerJoin(DB::qn('#__organizer_blocks', 'b'), DB::qc('b.id', 'i.blockID'))
            ->innerJoin(DB::qn('#__organizer_units', 'u'), DB::qc('u.id', 'i.unitID'))
            ->where(DB::qcs([['i.delta', 'removed', '!=', true], ['u.delta', 'removed', '!=', true], ['u.termID', $termID]]))
            ->where(DB::qn('i.methodID') . ' IS NOT NULL');

        $categoryID     = (int) $state->get('list.categoryID');
        $organizationID = (int) $state->get('list.organizationID');

        if ($categoryID or $organizationID) {
            $query->innerJoin(DB::qn('#__organizer_instance_persons', 'ip'), DB::qc('ip.instanceID', 'i.id'))
                ->innerJoin(DB::qn('#__organizer_instance_groups', 'ig'), DB::qc('ig.assocID', 'ip.id'))
                ->where(DB::qcs([['ip.delta', 'removed', '!=', true], ['ig.delta', 'removed', '!=', true]]));

            if ($categoryID) {
                $query->innerJoin(DB::qn('#__organizer_groups', 'g'), DB::qc('g.id', 'ig.groupID'))
                    ->where(DB::qc('g.categoryID', $categoryID));
            }

            if ($organizationID) {
                $query->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.groupID', 'ig.groupID'))
                    ->where(DB::qc('a.organizationID', $organizationID));
            }
        }

        //$statistic = (int) $state->get('list.statistic');

        /*if ($statistic === View::CAPACITY or $statistic === View::REGISTRATIONS) {
            $finals = new Methods();
            $finals->load(['code' => 'KLA']);

            $today        = date('Y-m-d');
            $earlier      = DB::qc('b.date', $today, '<', true);
            $today        = DB::qc('b.date', $today, '=', true);
            $earlierToday = "($today AND " . DB::qc('b.endTime', date('H:i:s'), '<', true) . ")";

            $query->where(DB::qc('i.methodID', $finals->id, '!='))->where("($earlier OR ($earlierToday))");

            if ($statistic === View::REGISTRATIONS) {
                $query->select(DB::qn('book.id', 'bookingID'))
                    ->innerJoin(
                        DB::qn('#__organizer_bookings', 'book'),
                        DB::qcs([['book.blockID', 'i.blockID'], ['book.unitID ', 'i.unitID']])
                    );
            }
        }*/

        return $query;
    }

    /** @inheritDoc */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);
        $state = $this->state;

        if ($statistic = $state->get('list.statistic')) {
            $organizationID = $state->get('list.organizationID');
            $categoryID     = $state->get('list.categoryID');

            if ($categoryID) {
                $organizationIDs = Categories::organizationIDs($categoryID);

                if ($organizationID) {
                    // Reset the category if it is not assigned to the selected organization
                    $categoryID = in_array($organizationID, $organizationIDs) ? $categoryID : '';
                    $state->set('categoryID', $categoryID);
                }
                elseif ($organizationIDs) {
                    $state->set('categoryID', $categoryID);
                    $state->set('organizationID', reset($organizationIDs));
                }
                // Potential error state
                else {
                    $state->set('list.categoryID', '');
                    $state->set('list.organizationID', '');
                }
            }

            // Perform sanity check
            $statistic = match ($statistic) {
                //View::CAPACITY => View::CAPACITY,
                View::PRESENCE_TYPE => View::PRESENCE_TYPE,
                //View::REGISTRATIONS => View::REGISTRATIONS,
                default => View::METHOD
            };
            $state->set('list.statistic', $statistic);
        }
        else {
            $state->set('list.categoryID', '');
            $state->set('list.organizationID', '');
            $state->set('list.statistic', View::METHOD);
        }

        if (!$state->get('list.termID')) {
            $state->set('list.termID', Terms::currentID());
        }

        $state->set('list.limit', 0);
        $state->set('list.start', 0);
    }
}
