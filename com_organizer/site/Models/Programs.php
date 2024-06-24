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

use Joomla\CMS\Form\Form;
use Joomla\Database\DatabaseQuery;
use THM\Organizer\Adapters\{Application, Database as DB};
use THM\Organizer\Helpers\{Can, Organizations, Programs as Helper};

/** @inheritDoc */
class Programs extends ListModel
{
    use Activated;

    protected $filter_fields = ['accredited', 'degreeID', 'frequencyID', 'organizationID'];

    /** @inheritDoc */
    public function filterFilterForm(Form $form): void
    {
        parent::filterFilterForm($form);

        if (Application::backend()) {
            if (count(Organizations::documentableIDs()) === 1) {
                $form->removeField('organizationID', 'filter');
                unset($this->filter_fields['organizationID']);
            }
        }

    }

    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        $query = Helper::query();

        if (Can::administrate()) {
            $query->select(DB::quote(1) . ' AS ' . DB::qn('access'));
        }
        elseif ($ids = Helper::documentableIDs()) {
            $query->select(DB::qn('p.id') . ' IN (' . implode(',', $ids) . ')' . ' AS ' . DB::qn('access'));
        }
        else {
            $query->select(DB::quote(0) . ' AS ' . DB::qn('access'));
        }

        $this->activeFilter($query, 'p');

        if (Application::backend()) {
            $this->filterByAccess($query, 'p', 'document');
        }

        $this->filterByOrganization($query, 'p');

        $searchColumns = ['p.name_de', 'p.name_en', 'accredited', 'd.name', 'description_de', 'description_en'];
        $this->filterSearch($query, $searchColumns);

        $this->filterValues($query, ['degreeID', 'frequencyID', 'accredited']);

        $this->orderBy($query);

        return $query;
    }

    /** @inheritDoc */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        if (Application::backend()) {
            $authorized = Organizations::documentableIDs();
            if (count($authorized) === 1) {
                $this->state->set('filter.organizationID', $authorized[0]);
            }
        }

    }
}
