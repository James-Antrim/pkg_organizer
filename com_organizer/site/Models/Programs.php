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

use JDatabaseQuery;
use Joomla\CMS\Form\Form;
use THM\Organizer\Adapters\Queries\QueryMySQLi;
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
    public function filterFilterForm(Form $form)
    {
        parent::filterFilterForm($form);

        if ($this->adminContext) {
            if (count(Helpers\Can::documentTheseOrganizations()) === 1) {
                $form->removeField('organizationID', 'filter');
                unset($this->filter_fields['organizationID']);
            }
        }

    }

    /**
     * @inheritDoc
     */
    protected function getListQuery(): JDatabaseQuery
    {
        /* @var QueryMySQLi $query */
        $query = Helpers\Programs::getQuery();

        $this->setActiveFilter($query, 'p');
        $this->setOrganizationFilter($query, 'program', 'p');

        $searchColumns = ['p.name_de', 'p.name_en', 'accredited', 'd.name', 'description_de', 'description_en'];
        $this->setSearchFilter($query, $searchColumns);

        $this->setValueFilters($query, ['degreeID', 'frequencyID', 'accredited']);

        $this->setOrdering($query);

        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function populateState($ordering = null, $direction = null)
    {
        parent::populateState($ordering, $direction);

        if ($this->adminContext) {
            $authorized = Helpers\Can::documentTheseOrganizations();
            if (count($authorized) === 1) {
                $this->state->set('filter.organizationID', $authorized[0]);
            }
        }

    }
}
