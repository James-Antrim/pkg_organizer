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
use THM\Organizer\Adapters\{Application, Database, Queries\QueryMySQLi};
use Joomla\Database\DatabaseQuery;
use THM\Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of (subject) pools.
 */
class Pools extends ListModel
{
    protected $filter_fields = [
        'organizationID' => 'organizationID',
        'fieldID'        => 'fieldID',
        'programID'      => 'programID'
    ];

    /**
     * @inheritDoc
     */
    public function filterFilterForm(Form $form): void
    {
        if (count(Helpers\Can::documentTheseOrganizations()) === 1) {
            $form->removeField('organizationID', 'filter');
            unset($this->filter_fields['organizationID']);
        }
    }

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $tag = Application::getTag();

        $query = Database::getQuery();
        $query->select("DISTINCT p.id, p.fullName_$tag AS name, p.fieldID")->from('pools AS p');

        $this->filterOrganizations($query, 'pool', 'p');

        $searchColumns = [
            'p.fullName_de',
            'p.abbreviation_de',
            'p.fullName_en',
            'p.abbreviation_en'
        ];
        $this->filterSearch($query, $searchColumns);
        $this->filterValues($query, ['fieldID']);

        if ($programID = (int) $this->state->get('filter.programID')) {
            Helpers\Pools::filterProgram($query, $programID, 'pool', 'p');
        }

        $this->orderBy($query);

        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        $authorized = Helpers\Can::documentTheseOrganizations();
        if (count($authorized) === 1) {
            $this->state->set('filter.organizationID', $authorized[0]);
        }
    }
}
