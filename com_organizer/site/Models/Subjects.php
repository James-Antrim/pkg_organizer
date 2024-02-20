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
use THM\Organizer\Adapters\{Application, Database as DB, Input, Text};
use THM\Organizer\Helpers\{Can, Organizations, Pools, Programs, Subjects as Helper};

/**
 * Class retrieves information for a filtered set of subjects.
 */
class Subjects extends ListModel
{
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
            if (count(Organizations::documentableIDs()) === 1) {
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

        $backend = Application::backend();
        $role    = Input::getParams()->get('role', 1);

        foreach ($items as $item) {
            $item->name = $item->name ?: sprintf(Text::_('SUBJECT_WITHOUT_NAME'), $item->id);
            if ($backend) {
                $item->program = Helper::programName($item->id);
            }
            else {
                $item->persons = Helper::persons($item->id, $role);
            }
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

        if (Application::backend()) {
            $this->filterByAccess($query, 's', 'document');
        }

        $this->filterByOrganization($query, 's');
        $this->filterSearch($query, $searchFields);

        if ($programID = (int) $this->state->get('filter.programID')) {
            Helper::filterProgram($query, $programID, 'subjectID', 's');
        }

        // The selected pool supersedes any original called pool
        if ($poolID = (int) $this->state->get('filter.poolID')) {
            Helper::filterPool($query, $poolID, 's', $programID);
        }

        if ($personID = (int) $this->state->get('filter.personID')) {
            $condition = DB::qc('sp.subjectID', 's.id');
            $table     = DB::qn('#__organizer_subject_persons', 'sp');
            if ($personID === self::NONE) {
                $conditions = [$condition, DB::qn('sp.subjectID') . ' IS NULL'];
                $query->leftJoin($table, $conditions);
            }
            else {
                $query->innerJoin($table, $condition)
                    ->where(DB::qn('sp.personID') . ' = :personID')->bind(':personID', $personID, ParameterType::INTEGER);
            }
        }

        $this->filterByKey($query, 's.fieldID', 'fieldID');
        $this->filterValues($query, ['language']);
        $this->orderBy($query);

        return $query;
    }

    /**
     * Checks whether the person in question is assigned to a subject in the given pool or program
     *
     * @param   int  $personID   the id of the person filtered against
     * @param   int  $programID  the optional id of the program being filtered against
     * @param   int  $poolID     the optional id of the pool being filtered against
     *
     * @return bool
     */
    private function plausiblePerson(int $personID, int $programID = 0, int $poolID = 0): bool
    {
        // Cannot check against an empty or negative context
        if ((!$programID and !$poolID) or $programID === self::NONE or $poolID === self::NONE) {
            return false;
        }

        // Empty result set
        if (!$subjects = $poolID ? Pools::subjects($poolID) : Programs::subjects($programID)) {
            return false;
        }

        $persons = [];
        foreach ($subjects as $subject) {
            $thesePeople = Helper::persons($subject['subjectID']);
            if (empty($thesePeople)) {
                continue;
            }

            $persons = array_merge($persons, $thesePeople);
        }

        if (empty($persons)) {
            return false;
        }

        foreach ($persons as $person) {
            if ($person['id'] === $personID) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        if (Application::backend()) {
            $authorized = Organizations::documentableIDs();
            if (count($authorized) === 1) {
                $organizationID = $authorized[0];
            }
            else {
                $organizationID = Input::getInt('organizationID');
            }
        }
        else {
            // Can only be explicitly called by a subjects filter where no called parameters prevented its output.
            $organizationID = Input::getInt('organizationID');
        }

        if ($organizationID) {
            $this->state->set('filter.organizationID', $organizationID);
        }

        $disassociated = $organizationID === self::NONE;
        $personID      = Input::getInt('personID');
        $poolID        = Input::getInt('poolID');
        $misconfigured = $poolID === self::NONE;
        $programID     = Input::getInt('programID');
        $lost          = $programID === self::NONE;

        // Curriculum plausibility checks //////////////////////////////////////////////////////////////////////////////

        // Subjects not associated with a program
        if ($lost) {
            // Pool selected in an invalid program context
            if ($poolID and !$misconfigured) {
                $poolID = 0;
            }
        }
        // Subjects associated with selected program
        elseif ($programID) {
            // Selected program context invalid => any concrete pool selection is consequently invalid
            if (!$prRanges = Programs::rows($programID)) {
                $poolID    = $misconfigured ? $poolID : '';
                $programID = 0;
            }
            // Concrete pool selected
            elseif ($poolID and !$misconfigured) {
                // Selected pool context is invalid or pool not available in program context
                if (!$plRanges = Pools::rows($poolID) or !Helper::included($plRanges, $prRanges)) {
                    $poolID = 0;
                }
            }
        }
        // Pool selected with no program context selected: error state
        elseif ($poolID and !$misconfigured) {
            $poolID = 0;
        }

        // Association plausibility checks /////////////////////////////////////////////////////////////////////////////

        // Concrete organization selected
        if ($organizationID and !$disassociated) {
            // Program selected not associated with selected organization: error state
            if ($programID and !$lost and !Programs::associated($organizationID, $programID)) {
                $programID = 0;
                $poolID    = $misconfigured ? $poolID : 0;
            }
            elseif ($poolID and !$misconfigured and !Pools::associated($organizationID, $poolID)) {
                $poolID = 0;
            }
        }

        if (Application::backend()) {

            $this->finalizeFilterState($personID, $programID, $poolID);

            return;
        }


        if (!$organizationID) {
            $cPersonID  = Input::getInt('personID', 0, 'GET') ?: (int) $this->state->get('calledPersonID', 0);
            $cPersonID  = $cPersonID === self::NONE ? 0 : $cPersonID;
            $cPoolID    = Input::getInt('poolID', 0, 'GET') ?: (int) $this->state->get('calledPoolID', 0);
            $cPoolID    = $cPoolID === self::NONE ? 0 : $cPoolID;
            $cProgramID = Input::getInt('programID', 0, 'GET') ?: (int) Input::getParams()->get('programID', 0);
            $cProgramID = $cProgramID ?: (int) $this->state->get('calledProgramID', 0);
            $cProgramID = $cProgramID === self::NONE ? 0 : $cProgramID;

            if ($cProgramID) {
                // Called program context invalid => any concrete pool called are consequently invalid
                if (!$prRanges = Programs::rows($cProgramID)) {
                    $cPoolID    = 0;
                    $cProgramID = 0;
                }
                // Concrete pool called
                elseif ($cPoolID) {
                    // Selected pool context is invalid or pool not available in program context
                    if (!$plRanges = Pools::rows($cPoolID) or !Helper::included($plRanges, $prRanges)) {
                        $cPoolID = 0;
                    }
                }
                // Concrete pool selected
                elseif ($poolID and !$misconfigured) {
                    // Selected pool context is invalid or pool not available in program context
                    if (!$plRanges = Pools::rows($poolID) or !Helper::included($plRanges, $prRanges)) {
                        $poolID = 0;
                    }
                }
            }

            // Preemptively dismiss pagination on configured calls and end here
            if ($cPersonID or $cPoolID or $cProgramID) {
                $cPersonID  = $cPersonID ?: null;
                $cPoolID    = $cPoolID ?: null;
                $cProgramID = $cProgramID ?: null;

                // Set ~final filter values
                $personID  = $cPersonID ?: $personID;
                $poolID    = $cPoolID ?: $poolID;
                $programID = $cProgramID ?: $programID;

                // Use filter values to check person plausibility
                $this->finalizeFilterState($personID, $programID, $poolID);

                $this->state->set('calledPersonID', $cPersonID);
                $this->state->set('calledPoolID', $cPoolID);
                $this->state->set('calledProgramID', $cProgramID);

                $this->setState('list.limit', 0);

                return;
            }
        }

        // Ensure clean cache
        $this->state->set('calledPersonID', null);
        $this->state->set('calledPoolID', null);
        $this->state->set('calledProgramID', null);


        $this->finalizeFilterState($personID, $programID, $poolID);
    }

    /**
     * Sets the resource id filter states after plausibility checks.
     *
     * @param   int  $personID   the id for the person filter
     * @param   int  $programID  the id for the program filter
     * @param   int  $poolID     the id for the p
     *
     * @return void
     */
    protected function finalizeFilterState(int $personID, int $programID, int $poolID): void
    {
        if ($personID and $personID !== self::NONE and ($programID or $poolID)) {
            $personID = $this->plausiblePerson($personID, $programID ?: 0, $poolID ?: 0) ? $personID : '';
        }

        $this->state->set('filter.personID', $personID ?: '');
        $this->state->set('filter.poolID', $poolID ?: '');
        $this->state->set('filter.programID', $programID ?: '');
    }
}
