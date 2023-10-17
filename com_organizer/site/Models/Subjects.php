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
use THM\Organizer\Adapters\{Application, Database, Input, Queries\QueryMySQLi};
use THM\Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of subjects.
 */
class Subjects extends ListModel
{
    protected $filter_fields = [
        'language' => 'language',
        'fieldID' => 'fieldID',
        'organizationID' => 'organizationID',
        'personID' => 'personID',
        'poolID' => 'poolID',
        'programID' => 'programID'
    ];

    /**
     * @inheritDoc
     */
    public function filterFilterForm(Form $form): void
    {
        parent::filterFilterForm($form);
        if (!empty($this->state->get('calledProgramID')) or !empty($this->state->get('calledPoolID'))) {
            $form->removeField('organizationID', 'filter');
            $form->removeField('limit', 'list');
            $form->removeField('programID', 'filter');
            unset($this->filter_fields['organizationID'], $this->filter_fields['programID']);
        }
        if (!empty($this->state->get('calledPersonID'))) {
            $form->removeField('organizationID', 'filter');
            $form->removeField('limit', 'list');
            $form->removeField('personID', 'filter');
            unset($this->filter_fields['organizationID'], $this->filter_fields['personID']);
        } elseif (Application::backend()) {
            if (count(Helpers\Can::documentTheseOrganizations()) === 1) {
                $form->removeField('organizationID', 'filter');
                unset($this->filter_fields['organizationID']);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getItems(): array
    {
        $items = parent::getItems();

        if (empty($items)) {
            return [];
        }

        $role = Input::getParams()->get('role', 1);

        foreach ($items as $item) {
            $item->persons = Helpers\Subjects::getPersons($item->id, $role);
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $tag = Application::getTag();

        // Create the sql query
        /* @var QueryMySQLi $query */
        $query = Database::getQuery();
        $query->select("DISTINCT s.id, s.code, s.fullName_$tag AS name, s.fieldID, s.creditPoints")
            ->from('subjects AS s');

        $searchFields = [
            's.fullName_de',
            's.abbreviation_de',
            's.fullName_en',
            's.abbreviation_en',
            's.code',
            's.lsfID'
        ];

        $this->setOrganizationFilter($query, 'subject', 's');
        $this->setSearchFilter($query, $searchFields);

        if ($programID = (int) $this->state->get('filter.programID')) {
            Helpers\Subjects::setProgramFilter($query, $programID, 'subject', 's');
        }

        // The selected pool supersedes any original called pool
        if ($poolID = $this->state->get('filter.poolID')) {
            Helpers\Subjects::setPoolFilter($query, $poolID, 's');
        }

        $personID = (int) $this->state->get('filter.personID');
        if ($personID !== self::ALL) {
            if ($personID === self::NONE) {
                $conditions = ['sp.subjectID = s.id', 'sp.subjectID IS NULL'];
                $query->leftJoinX('subject_persons AS sp', $conditions);
            } else {
                $query->innerJoinX('subject_persons AS sp', ['sp.subjectID = s.id'])->where("sp.personID = $personID");
            }
        }

        $this->setIDFilter($query, 's.fieldID', 'filter.fieldID');
        $this->setValueFilters($query, ['language']);
        $this->setOrdering($query);

        return $query;
    }

    /**
     * @inheritDoc
     */
    public function getTotal($idColumn = null)
    {
        return parent::getTotal('s.id');
    }

    /**
     * @inheritDoc
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        $calledPerson  = false;
        $calledPool    = false;
        $calledProgram = false;
        $personID      = self::ALL;
        $poolID        = self::ALL;
        $programID     = self::ALL;

        $organizationID = Input::getFilterID('organization', self::ALL);

        if (Application::backend()) {
            $authorized = Helpers\Can::documentTheseOrganizations();
            if (count($authorized) === 1) {
                $organizationID = $authorized[0];
                $this->state->set('filter.organizationID', $organizationID);
            }
        } else {
            // Program ID can be set by menu settings or the request
            if ($programID = Input::getInt('programID')
                or $programID = Input::getParams()->get('programID', 0)
                or $programID = $this->state->get('calledProgramID')) {
                $calledProgram = $programID;
            }

            // Pool ID can be set by the request
            if ($poolID = Input::getInt('poolID')
                or $poolID = $this->state->get('calledPoolID')) {
                $calledPool = $poolID;
            }

            // Person ID can be set by the request
            if ($personID = Input::getInt('personID')
                or $personID = $this->state->get('calledPersonID')) {
                $calledPerson = $personID;
            }
        }

        if ($calledPerson or $calledPool or $calledProgram) {
            $this->setState('list.limit', 0);
        }

        $personID    = $calledPerson ? $personID : Input::getFilterID('person', self::ALL);
        $defaultPool = $calledPool ?: self::ALL;
        $poolID      = $calledPool ? $poolID : Input::getFilterID('pool', $defaultPool);
        $programID   = $calledProgram ? $programID : Input::getFilterID('program', self::ALL);

        $this->state->set('calledPersonID', $calledPerson);
        $this->state->set('calledPoolID', false);
        $this->state->set('calledProgramID', false);

        $this->state->set('filter.personID', $personID);
        $this->state->set('filter.poolID', self::ALL);
        $this->state->set('filter.programID', self::ALL);

        // The existence of the organization id precludes called curriculum parameter use
        if ($organizationID) {
            // Pool and program filtering is completely disassociated subjects
            if ($organizationID === self::NONE) {
                return;
            }

            if ($programID) {
                // Disassociated subjects requested => precludes pool selections
                if ($programID === self::NONE) {
                    $this->state->set('filter.programID', $programID);

                    return;
                }

                // Selected program is incompatible with the selected organization => precludes pool selections
                if (!Helpers\Programs::isAssociated($organizationID, $programID)) {
                    return;
                }

            }

            if ($poolID) {
                if ($poolID === self::NONE) {
                    $this->state->set('filter.poolID', $poolID);
                    $this->state->set('filter.programID', $programID);

                    return;
                }

                if (!Helpers\Pools::isAssociated($organizationID, $poolID)) {
                    $this->state->set('filter.poolID', self::ALL);
                    $this->state->set('filter.programID', $programID);

                    return;
                }

                if ($programID) {
                    if (!$prRanges = Helpers\Programs::getRanges($programID)) {
                        return;
                    }

                    if (!$plRanges = Helpers\Pools::getRanges($poolID)
                        or !Helpers\Subjects::poolInProgram($plRanges, $prRanges)) {
                        $this->state->set('filter.poolID', self::ALL);
                        $this->state->set('filter.programID', $programID);

                        return;
                    }
                }
            }

            // Curriculum filters are either valid or empty
            $this->state->set('filter.poolID', $poolID);
            $this->state->set('filter.programID', $programID);

            return;
        }

        if ($calledPerson) {
            $this->state->set('calledPersonID', $calledPerson);
            $this->state->set('filter.personID', $personID);
            $this->state->set('filter.programID', $programID);
            $this->state->set('filter.poolID', $poolID);
        }

        if ($programID === self::NONE) {
            $this->state->set('filter.programID', $programID);

            return;
        }

        if (!$poolID) {
            if ($calledProgram) {
                $this->state->set('calledProgramID', $calledProgram);
            }

            $this->state->set('filter.programID', $programID);

            return;
        }

        if ($poolID === self::NONE) {
            if ($programID) {
                if ($calledProgram) {
                    $this->state->set('calledProgramID', $programID);
                }

                $this->state->set('filter.poolID', $poolID);
                $this->state->set('filter.programID', $programID);
            } else {
                $this->state->set('filter.poolID', self::ALL);
                $this->state->set('filter.programID', self::NONE);
            }

            return;
        }

        // The existence of a program id precludes the pool having been called directly
        if ($programID) {
            // None has already been eliminated => the chosen program is invalid => allow reset
            if (!$prRanges = Helpers\Programs::getRanges($programID)) {
                return;
            }


            $this->state->set('filter.programID', $programID);

            // Pool is invalid or invalid for the chosen program context
            if (!$plRanges = Helpers\Pools::getRanges($poolID)
                or !Helpers\Subjects::poolInProgram($plRanges, $prRanges)) {
                return;
            }

            if ($calledPool) {
                $this->state->set('calledPoolID', $calledPool);
            } elseif ($calledProgram) {
                $this->state->set('calledProgramID', $calledProgram);
            }

            $this->state->set('filter.poolID', $poolID);

            return;
        }

        if ($calledPool) {
            $this->state->set('calledPoolID', $calledPool);
        }

        $this->state->set('filter.poolID', $poolID);
    }
}
