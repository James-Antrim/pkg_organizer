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
use Joomla\Database\{DatabaseQuery, ParameterType};
use THM\Organizer\Adapters\{Application, Database as DB, Input};
use THM\Organizer\Helpers\{Can, Pools, Programs, Subjects as Helper};

/**
 * Class retrieves information for a filtered set of subjects.
 */
class Subjects extends ListModel
{
    private const ALL = 0;

    protected $filter_fields = [
        'language'       => 'language',
        'fieldID'        => 'fieldID',
        'organizationID' => 'organizationID',
        'personID'       => 'personID',
        'poolID'         => 'poolID',
        'programID'      => 'programID'
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
        }
        elseif (Application::backend()) {
            if (count(Can::documentTheseOrganizations()) === 1) {
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
        if (!$items = parent::getItems()) {
            return [];
        }

        $role = Input::getParams()->get('role', 1);

        foreach ($items as $item) {
            $item->persons = Helper::persons($item->id, $role);
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::getQuery();
        $tag   = Application::getTag();
        $url   = 'index.php?option=com_organizer&view=Subject&id=';


        if (Can::administrate()) {
            $access = DB::quote(1) . ' AS ' . DB::qn('access');
        }
        elseif ($ids = Helper::documentableIDs()) {
            $access = DB::qn('s.id') . ' IN (' . implode(',', $ids) . ')' . ' AS ' . DB::qn('access');
        }
        else {
            $access = DB::quote(0) . ' AS ' . DB::qn('access');
        }

        $select = [
            'DISTINCT ' . DB::qn('s.id'),
            DB::qn('s.code'),
            DB::qn('s.creditPoints'),
            DB::qn("s.fullName_$tag", 'name'),
            DB::qn('s.fieldID'),
            $query->concatenate([DB::quote($url), DB::qn('s.id')], '') . ' AS ' . DB::qn('url'),
            $access,
        ];

        $query->select($select)
            ->from(DB::qn('#__organizer_subjects', 's'));

        $searchFields = [
            's.fullName_de',
            's.abbreviation_de',
            's.fullName_en',
            's.abbreviation_en',
            's.code',
            's.lsfID'
        ];

        $this->filterOrganizations($query, 'subject', 's');
        $this->filterSearch($query, $searchFields);

        if ($programID = (int) $this->state->get('filter.programID')) {
            Helper::filterProgram($query, $programID, 'subjectID', 's');
        }

        // The selected pool supersedes any original called pool
        if ($poolID = $this->state->get('filter.poolID')) {
            Helper::filterPool($query, $poolID, 's');
        }

        $personID = (int) $this->state->get('filter.personID');
        if ($personID !== self::ALL) {
            $condition = DB::qc('sp.subjectID', 's.id');
            $table     = DB::qn('#__organizer_subject_persons', 'sp');
            if ($personID === self::NONE) {
                $conditions = [$condition, DB::qn('sp.subjectID') . ' IS NULL'];
                $query->leftJoin($table, $conditions);
            }
            else {
                $query->innerJoin($table, ['sp.subjectID = s.id'])
                    ->where(DB::qn('sp.personID') . ' = :personID')->bind(':personID', $personID, ParameterType::INTEGER);
            }
        }

        $this->filterID($query, 's.fieldID', 'filter.fieldID');
        $this->filterValues($query, ['language']);
        $this->orderBy($query);

        return $query;
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
            $authorized = Can::documentTheseOrganizations();
            if (count($authorized) === 1) {
                $organizationID = $authorized[0];
                $this->state->set('filter.organizationID', $organizationID);
            }
        }
        else {
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
                if (!Programs::associated($organizationID, $programID)) {
                    return;
                }

            }

            if ($poolID) {
                if ($poolID === self::NONE) {
                    $this->state->set('filter.poolID', $poolID);
                    $this->state->set('filter.programID', $programID);

                    return;
                }

                if (!Pools::associated($organizationID, $poolID)) {
                    $this->state->set('filter.poolID', self::ALL);
                    $this->state->set('filter.programID', $programID);

                    return;
                }

                if ($programID) {
                    if (!$prRanges = Programs::rows($programID)) {
                        return;
                    }

                    if (!$plRanges = Pools::rows($poolID)
                        or !Helper::included($plRanges, $prRanges)) {
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
            }
            else {
                $this->state->set('filter.poolID', self::ALL);
                $this->state->set('filter.programID', self::NONE);
            }

            return;
        }

        // The existence of a program id precludes the pool having been called directly
        if ($programID) {
            // None has already been eliminated => the chosen program is invalid => allow reset
            if (!$prRanges = Programs::rows($programID)) {
                return;
            }


            $this->state->set('filter.programID', $programID);

            // Pool is invalid or invalid for the chosen program context
            if (!$plRanges = Pools::rows($poolID)
                or !Helper::included($plRanges, $prRanges)) {
                return;
            }

            if ($calledPool) {
                $this->state->set('calledPoolID', $calledPool);
            }
            elseif ($calledProgram) {
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
