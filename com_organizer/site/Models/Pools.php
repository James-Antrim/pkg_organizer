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
use THM\Organizer\Adapters\Database;
use THM\Organizer\Adapters\Queries\QueryMySQLi;
use THM\Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of (subject) pools.
 */
class Pools extends ListModel
{
    protected $filter_fields = [
        'organizationID' => 'organizationID',
        'fieldID' => 'fieldID',
        'programID' => 'programID'
    ];

    /**
     * @inheritDoc
     */
    public function filterFilterForm(Form $form)
    {
        if (count(Helpers\Can::documentTheseOrganizations()) === 1) {
            $form->removeField('organizationID', 'filter');
            unset($this->filter_fields['organizationID']);
        }
    }

    /**
     * @inheritDoc
     */
    protected function getListQuery()
    {
        $tag = Helpers\Languages::getTag();

        /* @var QueryMySQLi $query */
        $query = Database::getQuery();
        $query->select("DISTINCT p.id, p.fullName_$tag AS name, p.fieldID")->from('pools AS p');

        $this->setOrganizationFilter($query, 'pool', 'p');

        $searchColumns = [
            'p.fullName_de',
            'p.abbreviation_de',
            'p.fullName_en',
            'p.abbreviation_en'
        ];
        $this->setSearchFilter($query, $searchColumns);
        $this->setValueFilters($query, ['fieldID']);

        if ($programID = (int) $this->state->get('filter.programID')) {
            Helpers\Pools::setProgramFilter($query, $programID, 'pool', 'p');
        }

        $this->setOrdering($query);

        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function populateState($ordering = null, $direction = null)
    {
        parent::populateState($ordering, $direction);

        $authorized = Helpers\Can::documentTheseOrganizations();
        if (count($authorized) === 1) {
            $this->state->set('filter.organizationID', $authorized[0]);
        }
    }
}
