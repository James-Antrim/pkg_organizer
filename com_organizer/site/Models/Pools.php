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
use THM\Organizer\Adapters\{Application, Database as DB};
use Joomla\Database\DatabaseQuery;
use THM\Organizer\Helpers\{Can, Organizations, Pools as Helper};

/** @inheritDoc */
class Pools extends ListModel
{
    protected $filter_fields = ['organizationID', 'fieldID', 'programID'];

    /** @inheritDoc */
    public function filterFilterForm(Form $form): void
    {
        if (count(Organizations::documentableIDs()) === 1) {
            $form->removeField('organizationID', 'filter');
            unset($this->filter_fields['organizationID']);
        }
    }

    /** @inheritDoc */
    public function getItems(): array
    {
        if (!$items = parent::getItems()) {
            return [];
        }

        foreach ($items as $item) {
            $item->programs = Helper::programs($item->id);
        }

        return $items;
    }

    /**
     * @inheritDoc
     * @todo get the program name here as part of the query
     */
    protected function getListQuery(): DatabaseQuery
    {
        if (Can::administrate()) {
            $access = DB::quote(1) . ' AS ' . DB::qn('access');
        }
        elseif ($ids = Helper::documentableIDs()) {
            $access = DB::qn('p.id') . ' IN (' . implode(',', $ids) . ')' . ' AS ' . DB::qn('access');
        }
        else {
            $access = DB::quote(0) . ' AS ' . DB::qn('access');
        }

        $query = DB::query();
        $url   = 'index.php?option=com_organizer&view=pool&id=';

        $select = [
            'DISTINCT ' . DB::qn('p.id'),
            $access,
            DB::qn('p.fieldID'),
            DB::qn('p.fullName_' . Application::tag(), 'name'),
            $query->concatenate([DB::quote($url), DB::qn('p.id')], '') . ' AS ' . DB::qn('url')
        ];

        $query->select($select)->from(DB::qn('#__organizer_pools', 'p'));

        if (Application::backend()) {
            $this->filterByAccess($query, 'p', 'document');
        }

        $this->filterByOrganizations($query, 'p');

        $searchColumns = ['p.fullName_de', 'p.abbreviation_de', 'p.fullName_en', 'p.abbreviation_en'];
        $this->filterSearch($query, $searchColumns);
        $this->filterValues($query, ['fieldID']);

        if ($programID = (int) $this->state->get('filter.programID')) {
            Helper::filterProgram($query, $programID, 'poolID', 'p');
        }

        $this->orderBy($query);

        return $query;
    }

    /** @inheritDoc */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        $authorized = Organizations::documentableIDs();
        if (count($authorized) === 1) {
            $this->state->set('filter.organizationID', $authorized[0]);
        }
    }
}
