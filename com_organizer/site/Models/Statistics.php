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
use THM\Organizer\Adapters\{Database as DB, Form};
use THM\Organizer\Helpers\{Categories, Methods, Terms};

/**
 * Class calculates lesson statistics and loads them into the view context.
 */
class Statistics extends ListModel
{
    /** @inheritDoc */
    protected function filterFilterForm(Form $form): void
    {
        if (!$this->state->get('list.organizationID')) {
            $form->removeField('categoryID', 'list');
        }
    }

    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        $state  = $this->state;
        $termID = (int) $state->get('list.termID');

        $query = DB::getQuery();
        $query->select([DB::qn('u.id', 'unitID'), DB::qn('blockID'), DB::qn('methodID'), DB::qn('date')])
            ->from(DB::qn('#__organizer_units', 'u'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('u.id', 'i.unitID'))
            ->innerJoin(DB::qn('#__organizer_blocks', 'b'), DB::qc('b.id', 'i.blockID'))
            ->innerJoin(DB::qn('#__organizer_instance_persons', 'ip'), DB::qc('ip.instanceID', 'i.id'))
            ->innerJoin(DB::qn('#__organizer_instance_groups', 'ig'), DB::qc('ig.assocID', 'ip.id'))
            ->innerJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.groupID', 'ig.groupID'))
            ->innerJoin(DB::qn('#__organizer_groups', 'g'), DB::qc('g.id', 'ig.groupID'))
            ->group(DB::qn(['unitID', 'blockID']))
            ->where(DB::qcs([
                ['i.delta', 'removed', '!=', true],
                ['ig.delta', 'removed', '!=', true],
                ['ip.delta', 'removed', '!=', true],
                ['u.delta', 'removed', '!=', true],
                ['u.termID', $termID]
            ]))
            ->whereIn(DB::qn('i.methodID'), Methods::relevant());

        if ($categoryID = (int) $state->get('list.categoryID')) {
            $query->select($query->groupConcat('DISTINCT ' . DB::qn('g.id')) . ' AS ' . DB::qn('resourceIDs'))
                ->where(DB::qc('g.categoryID', $categoryID));
        }
        else {
            if ($organizationID = (int) $state->get('list.organizationID')) {
                $query->select($query->groupConcat('DISTINCT ' . DB::qn('g.categoryID')) . ' AS ' . DB::qn('resourceIDs'))
                    ->where(DB::qc('a.organizationID', $organizationID));;
            }
            else {
                $query->select($query->groupConcat('DISTINCT ' . DB::qn('a.organizationID')) . ' AS ' . DB::qn('resourceIDs'));
            }
        }

        return $query;
    }

    /** @inheritDoc */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);
        $state = $this->state;

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

        if (!$state->get('list.termID')) {
            $state->set('list.termID', Terms::currentID());
        }

        $state->set('list.limit', 0);
        $state->set('list.start', 0);
    }
}
