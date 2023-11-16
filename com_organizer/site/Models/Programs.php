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
use THM\Organizer\Adapters\Application;
use THM\Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of (degree) programs.
 */
class Programs extends ListModel
{
    use Activated;

    protected $filter_fields = ['accredited', 'degreeID', 'frequencyID', 'organizationID'];

    /**
     * @inheritDoc
     */
    public function filterFilterForm(Form $form): void
    {
        parent::filterFilterForm($form);

        if (Application::backend()) {
            if (count(Helpers\Can::documentTheseOrganizations()) === 1) {
                $form->removeField('organizationID', 'filter');
                unset($this->filter_fields['organizationID']);
            }
        }

    }

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $query = Helpers\Programs::getQuery();

        $this->filterActive($query, 'p');
        $this->filterOrganizations($query, 'program', 'p');

        $searchColumns = ['p.name_de', 'p.name_en', 'accredited', 'd.name', 'description_de', 'description_en'];
        $this->filterSearch($query, $searchColumns);

        $this->filterValues($query, ['degreeID', 'frequencyID', 'accredited']);

        $this->orderBy($query);

        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        if (Application::backend()) {
            $authorized = Helpers\Can::documentTheseOrganizations();
            if (count($authorized) === 1) {
                $this->state->set('filter.organizationID', $authorized[0]);
            }
        }

    }
}
