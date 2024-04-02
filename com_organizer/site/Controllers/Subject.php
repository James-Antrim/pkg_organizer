<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use Exception;
use Joomla\Database\ParameterType;
use SimpleXMLElement;
use THM\Organizer\Adapters\{Application, Database as DB, Input, Text};
use THM\Organizer\Helpers\{LSF, Persons, Programs, Subjects as Helper};
use THM\Organizer\{Tables, Tables\Subjects as Table};

/**
 * @inheritDoc
 */
class Subject extends CurriculumResource implements Stubby
{
    private const POST = 0, PRE = 1;

    protected string $list = 'Subjects';

    /**
     * Adds a Subject => Event association. No access checks => this is not directly accessible and requires
     * differing checks according to its calling context.
     *
     * @param   int    $subjectID  the id of the subject
     * @param   array  $eventIDs   the ids of the events
     *
     * @return bool  true on success, otherwise false
     */
    /*private function addEvents(int $subjectID, array $eventIDs)
    {
        // add int[] cast to eventIDs
        $query = Database::getQuery();
        $query->insert('#__organizer_subject_events')->columns('subjectID, eventID');

        foreach ($eventIDs as $eventID)
        {
            $query->values("$subjectID, $eventID");
        }

        Database::setQuery($query);

        return Database::execute();
    }*/

    /**
     * Processes the events to be associated with the subject
     *
     * @param   array &$data  the post data
     *
     * @return bool  true on success, otherwise false
     */
    /*private function processEvents(array &$data)
    {
        if (!isset($data['courseIDs']))
        {
            return true;
        }

        $subjectID = $data['id'];

        if (!$this->removeEvents($subjectID))
        {
            return false;
        }
        if (!empty($data['eventIDs']))
        {
            if (!$this->addEvents($subjectID, $data['eventIDs']))
            {
                return false;
            }
        }

        return true;
    }*/

    /**
     * Removes planSubject associations for the given subject. No access checks => this is not directly accessible and
     * requires differing checks according to its calling context.
     *
     * @param   int  $subjectID  the subject id
     *
     * @return bool
     */
    /*private function removeEvents(int $subjectID)
    {
        $query = Database::getQuery();
        $query->delete('#__organizer_subject_curricula')->where("subjectID = $subjectID");
        Database::setQuery($query);

        return Database::execute();
    }*/

    /**
     * Creates an association between persons, subjects and their roles for that subject.
     *
     * @param   int               $subjectID   the id of the subject
     * @param   SimpleXMLElement  $dataObject  an object containing the lsf response
     *
     * @return bool  true on success, otherwise false
     */
    private function assign(int $subjectID, SimpleXMLElement $dataObject): bool
    {
        $coordinators = $dataObject->xpath('//verantwortliche');
        $persons      = $dataObject->xpath('//dozent');

        if (!$this->unassign($subjectID)) {
            return false;
        }

        if (empty($coordinators) and empty($persons)) {
            return true;
        }

        if (!$this->assignByRole($subjectID, $coordinators, Persons::COORDINATES)) {
            return false;
        }

        if (!$this->assignByRole($subjectID, $persons, Persons::TEACHES)) {
            return false;
        }

        return true;
    }

    /**
     * Maps persons, roles and subjects.
     *
     * @param   int    $subjectID  the subject's id
     * @param   array  $persons    an array containing information about the subject's persons
     * @param   int    $role       the person's role
     *
     * @return bool
     */
    private function assignByRole(int $subjectID, array $persons, int $role): bool
    {
        if (empty($persons)) {
            return true;
        }

        $fnAttribute = $role == Persons::COORDINATES ? 'vorname' : 'personal.vorname';
        $snAttribute = $role == Persons::COORDINATES ? 'nachname' : 'personal.nachname';

        foreach ($persons as $person) {
            $pData = [];

            if (!$pData['surname'] = trim((string) $person->personinfo->$snAttribute)) {
                continue;
            }

            if (!$pData['username'] = trim((string) $person->hgnr)) {
                continue;
            }

            $criteria   = [];
            $criteria[] = ['username' => $pData['username']];

            if ($pData['forename'] = (string) $person->personinfo->$fnAttribute) {
                $criteria[] = ['surname' => $pData['surname'], 'forename' => $pData['forename']];
            }

            $pTable = new Tables\Persons();

            foreach ($criteria as $criterion) {
                if ($pTable->load($criterion)) {
                    break;
                }
            }

            if (!$pTable->id and !$pTable->save($pData)) {
                return false;
            }

            $spTable = new Tables\SubjectPersons();

            if (!$spTable->save(['personID' => $pTable->id, 'role' => $role, 'subjectID' => $subjectID])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Processes assignments from the form.
     *
     * @param   array  $data  the post data
     *
     * @return bool
     */
    private function assignments(array $data): bool
    {
        // More efficient to remove all subject persons associations for the subject than iterate the persons table
        if (!$this->unassign($data['id'])) {
            return false;
        }

        $coordinators = empty($data['coordinators']) ? [] : $data['coordinators'];
        $teachers     = empty($data['persons']) ? [] : $data['persons'];

        if (!$coordinators and !$teachers) {
            return true;
        }

        if ($coordinators and $persons = array_filter($coordinators)) {
            foreach ($persons as $personID) {
                $spData = ['personID' => $personID, 'role' => Persons::COORDINATES, 'subjectID' => $data['id']];
                $table  = new Tables\SubjectPersons();

                if (!$table->save($spData)) {
                    return false;
                }
            }

        }

        if ($teachers and $persons = array_filter($teachers)) {
            foreach ($persons as $personID) {
                $spData = ['personID' => $personID, 'role' => Persons::TEACHES, 'subjectID' => $data['id']];
                $table  = new Tables\SubjectPersons();

                if (!$table->save($spData)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Parses the object and sets table properties.
     *
     * @param   Table             $table    the subjects table object
     * @param   SimpleXMLElement  $subject  an object representing the data from the LSF response
     *
     * @return void
     */
    private function attributes(Table $table, SimpleXMLElement $subject): void
    {
        $table->setColumn('code', (string) $subject->modulecode, '');
        $table->setColumn('language', (string) $subject->sprache, '');
        $table->setColumn('frequencyID', (string) $subject->turnus, '');

        $durationExists = preg_match('/\d+/', (string) $subject->dauer, $duration);
        $durationValue  = empty($durationExists) ? 1 : (int) $duration[0];
        $table->setColumn('duration', $durationValue, 1);

        // Ensure reset before iterative processing
        $table->setColumn('creditPoints', 0, 0);

        // Attributes that can be set by text or individual fields
        $this->calculatedAttributes($table, $subject);

        foreach ($subject->xpath('//blobs/blob') as $objectNode) {
            $this->objectAttributes($table, $objectNode);
        }

        self::checkProofAndMethod($table);
    }

    /**
     * Sets attributes dealing with the availability to improve grades through supplemental learning.
     *
     * @param   Table   $table  the subjects table object
     * @param   string  $text   the bonus text
     *
     * @return void
     */
    private function bonus(Table $table, string $text): void
    {
        // Remove tags and indiscriminate left spacing then standardize as lower for comparisons.
        $text = strtolower(trim(strip_tags($text)));

        $hardNo = (empty($text) or str_contains($text, 'nein') or str_contains($text, 'kein') or $text === '-');

        if ($hardNo) {
            $table->bonusPoints = false;

            return;
        }

        // Hard yes
        if (str_contains($text, 'ja') or $text === '1') {
            $table->bonusPoints = true;

            return;
        }

        /**
         * Only explanatory text => implied no
         * Explanatory text for exam prerequisites => implied error => no
         */
        if (str_starts_with($text, 'bonus') or str_starts_with($text, 'prüfungsvorleistung')) {
            $table->bonusPoints = false;

            return;
        }

        $table->bonusPoints = true;
    }

    /**
     * Checks for best fit values for credit points, hours of investiture and the proportion of hours in independent study and
     * presence.
     *
     * @param   Table             $table    the subjects table object
     * @param   SimpleXMLElement  $subject  the subject object
     *
     * @return void
     */
    private function calculatedAttributes(Table $table, SimpleXMLElement $subject): void
    {
        if (!empty($subject->sws)) {
            $table->setColumn('sws', (int) $subject->sws, 0);
        }

        if (empty($subject->lp)) {
            $table->setColumn('creditPoints', 0, 0);
            $table->setColumn('expenditure', 0, 0);
            $table->setColumn('present', 0, 0);
            $table->setColumn('independent', 0, 0);

            return;
        }

        $crp = (int) $subject->lp;

        $table->setColumn('creditPoints', $crp, 0);

        $expenditure = empty($subject->aufwand) ? $crp * 30 : (int) $subject->aufwand;
        $table->setColumn('expenditure', $expenditure, 0);

        $validSum = false;
        if ($subject->praesenzzeit and $subject->selbstzeit) {
            $validSum = ((int) $subject->praesenzzeit + (int) $subject->selbstzeit) == $expenditure;
        }

        if ($validSum) {
            $table->setColumn('present', (int) $subject->praesenzzeit, 0);
            $table->setColumn('independent', (int) $subject->selbstzeit, 0);

            return;
        }

        $independent = 0;
        $presence    = 0;

        // I let required presence time take priority
        if ($subject->praesenzzeit) {
            $presence    = (int) $subject->praesenzzeit;
            $independent = $expenditure - $presence;
        }
        elseif ($subject->selbstzeit) {
            $independent = (int) $subject->selbstzeit;
            $presence    = $expenditure - $independent;
        }

        $table->setColumn('present', $presence, 0);
        $table->setColumn('independent', $independent, 0);
    }

    /**
     * Sets text values describing competences recommended as a prerequisite for subject attendance or competences acquired by
     * subject attendance.
     *
     * @param   Table   $table      the subjects table object
     * @param   string  $attribute  the attribute's name in the xml response
     * @param   string  $deValue    the attribute's German value
     * @param   string  $enValue    the attribute's English value
     *
     * @return void
     */
    private function competences(Table $table, string $attribute, string $deValue, string $enValue): void
    {
        switch ($attribute) {
            case 'Fachkompetenz':
                $deName = 'expertise_de';
                $enName = 'expertise_en';
                break;
            case 'Methodenkompetenz':
                $deName = 'methodCompetence_de';
                $enName = 'methodCompetence_en';
                break;
            case 'Sozialkompetenz':
                $deName = 'socialCompetence_de';
                $enName = 'socialCompetence_en';
                break;
            case 'Selbstkompetenz':
                $deName = 'selfCompetence_de';
                $enName = 'selfCompetence_en';
                break;
            default:
                return;
        }

        if ($deValue === '') {
            $table->$deName = '';
            $table->$enName = '';

            return;
        }

        if (preg_match('/^\d/', $deValue)) {
            $deValue = trim(substr($deValue, 1));

            if (str_starts_with($deValue, '<br>')) {
                $deValue = trim(substr($deValue, 4));
            }
        }

        if (preg_match('/^\d/', $enValue)) {
            $enValue = trim(substr($enValue, 1));

            if (str_starts_with($enValue, '<br>')) {
                $enValue = trim(substr($enValue, 4));
            }
        }

        $table->$deName = $deValue;
        $table->$enName = $enValue;
    }

    /**
     * Checks whether proof and method values are valid and set, and filling them with values from other languages if necessary
     * and available.
     *
     * @param   Table  $table  the subjects table object
     *
     * @return void
     */
    public function checkProofAndMethod(Table $table): void
    {
        if ((empty($table->proof_en) or strlen($table->proof_en) < 4) and !empty($table->proof_de)) {
            $table->proof_en = $table->proof_de;
        }

        if ((empty($table->method_en) or strlen($table->method_en) < 4) and !empty($table->method_de)) {
            $table->method_en = $table->method_de;
        }
    }

    /**
     * Sets attributes dealing with required student expenditures.
     *
     * @param   Table   $table  the subjects table object
     * @param   string  $text   the expenditure text
     *
     * @return void
     */
    private function expenditures(Table $table, string $text): void
    {
        $crpMatch = [];
        preg_match('/(\d) CrP/', $text, $crpMatch);
        if (!empty($crpMatch[1])) {
            $table->setColumn('creditPoints', $crpMatch[1], 0);
        }

        $hoursMatches = [];
        preg_match_all('/(\d+)+ Stunden/', $text, $hoursMatches);
        if (!empty($hoursMatches[1])) {
            $table->setColumn('expenditure', $hoursMatches[1][0], 0);
            if (!empty($hoursMatches[1][1])) {
                $table->setColumn('present', $hoursMatches[1][1], 0);
            }

            if (!empty($hoursMatches[1][2])) {
                $table->setColumn('independent', $hoursMatches[1][2], 0);
            }
        }
    }

    /**
     * Filters subject ranges to those contained within a given program range.
     *
     * @param   array  $programRange   the program range being iterated
     * @param   array  $subjectRanges  the ranges for the given subject
     *
     * @return array[]
     */
    private function filterRanges(array $programRange, array $subjectRanges): array
    {
        $left           = $programRange['lft'];
        $relevantRanges = [];
        $right          = $programRange['rgt'];

        foreach ($subjectRanges as $subjectRange) {
            if ($subjectRange['lft'] > $left and $subjectRange['rgt'] < $right) {
                $relevantRanges[] = $subjectRange;
            }
        }

        return $relevantRanges;
    }

    /**
     * Filters text of excess white space, HTML tags, and irrelevant characters to reduce the parse load during pre- & post-
     * requisite resolution.
     *
     * @param   string  $text  the text to be processed
     *
     * @return string
     */
    private function filterText(string $text): string
    {
        // Get rid of HTML tags & entities
        $text = preg_replace('/<[^>]+>/', ' ', $text);
        $text = html_entity_decode($text);

        // Remove any non alphanum characters
        $text = preg_replace('/[^a-zA-Z\d ]/', ' ', $text);

        // Remove excess white space
        $text = trim($text);

        return preg_replace('/\s+/', ' ', $text);
    }

    /**
     * @inheritDoc
     */
    public function import(int $resourceID): bool
    {
        $table = new Table();

        if (!$table->load($resourceID)) {
            Application::message('412', Application::ERROR);
            return false;
        }

        if (empty($table->lsfID)) {
            Application::message('LSF_ID_MISSING', Application::WARNING);
            return false;
        }

        try {
            $client = new LSF();
        }
        catch (Exception) {
            Application::message('LSF_CLIENT_FAILED', Application::ERROR);

            return false;
        }

        $response = $client->getModule($table->lsfID);

        if (empty($response->modul)) {
            $message = Text::sprintf('LSF_RESPONSE_EMPTY', $table->lsfID);
            Application::message($message, Application::NOTICE);

            return $this->delete($table->id);
        }

        $subject = $response->modul;

        if (!$this->validTitle($subject)) {
            $message = Text::sprintf('IMPORT_TITLE_INVALID', $table->lsfID);
            Application::message($message, Application::ERROR);

            return $this->delete($table->id);
        }

        $tag           = Application::getTag();
        $titleProperty = "titel$tag";
        $title         = $subject->$titleProperty;

        // Suppressed after title validation for use in message.
        if (!empty($subject->sperrmh) and strtolower((string) $subject->sperrmh) === 'x') {
            $message = Text::sprintf('SUBJECT_SUPPRESSED', $title, $table->lsfID);
            Application::message($message, Application::NOTICE);

            return $this->delete($table->id);
        }

        if (!$this->assign($table->id, $subject)) {
            Application::message('SAVE_FAIL', Application::ERROR);

            return false;
        }

        $this->setNames($table, $subject);

        $this->attributes($table, $subject);

        if (!$table->store()) {
            return false;
        }

        return $this->resolve($table);
    }

    /**
     * Sets text/html based attributes.
     *
     * @param   Table             $table     the subjects table object
     * @param   SimpleXMLElement  $property  the object containing a text blob
     *
     * @return void
     */
    private function objectAttributes(Table $table, SimpleXMLElement $property): void
    {
        $category = (string) $property->kategorie;

        /**
         * SimpleXML is terrible with mixed content. Since there is no guarantee what a node's format is,
         * this needs to be processed manually.
         */

        // German entries are the standard.
        if (empty($property->de->txt)) {
            $germanText  = '';
            $englishText = '';
        }
        else {
            $germanText  = $this->sanitizeText((string) $property->de->txt->FormattedText->asXML());
            $englishText = empty($property->en->txt) ? '' : $this->sanitizeText((string) $property->en->txt->FormattedText->asXML());
        }

        switch ($category) {
            case 'Aufteilung des Arbeitsaufwands':
                // There are int fields handled elsewhere for this, hopefully.
                if (!$table->creditPoints) {
                    $this->expenditures($table, $germanText);
                }
                break;

            case 'Bonuspunkte':
                $this->bonus($table, $germanText);
                break;

            case 'Empfohlene Voraussetzungen':
                $table->setColumn('recommendedPrerequisites_de', $germanText, '');
                $table->setColumn('recommendedPrerequisites_en', $englishText, '');
                break;

            case 'Inhalt':
                $table->setColumn('content_de', $germanText, '');
                $table->setColumn('content_en', $englishText, '');
                break;

            case 'Kurzbeschreibung':
                $table->setColumn('description_de', $germanText, '');
                $table->setColumn('description_en', $englishText, '');
                break;

            case 'Lehrformen':
                $table->setColumn('method_de', strip_tags($germanText), '');
                $table->setColumn('method_en', strip_tags($englishText), '');
                break;

            case 'Literatur':
                // This should never have been implemented with multiple languages
                $litText = $germanText ?: $englishText;
                $table->setColumn('literature', $litText, '');
                break;

            case 'Prüfungsvorleistungen':
                $table->setColumn('preliminaryWork_de', $germanText, '');
                $table->setColumn('preliminaryWork_en', $englishText, '');
                break;

            case 'Qualifikations und Lernziele':
                $table->setColumn('objective_de', $germanText, '');
                $table->setColumn('objective_en', $englishText, '');
                break;

            case 'Voraussetzungen':
                $table->setColumn('prerequisites_de', $germanText, '');
                $table->setColumn('prerequisites_en', $englishText, '');
                break;

            case 'Voraussetzungen für die Vergabe von Creditpoints':
                $table->setColumn('proof_de', $germanText, '');
                $table->setColumn('proof_en', $englishText, '');
                break;

            case 'Fachkompetenz':
            case 'Methodenkompetenz':
            case 'Sozialkompetenz':
            case 'Selbstkompetenz':
                $this->competences($table, $category, $germanText, $englishText);
                break;
        }
    }

    /**
     * Checks whether the text only consists of references to pre- & post- requisites.
     *
     * @param   string  $text           the text to be checked
     * @param   array   $attributes     the attributes whose values are considered to be human-readable references
     * @param   array   $codeGroupings  array code (module number) => [curriculumID => subject information]
     *
     * @return bool
     */
    private function onlyReferences(string $text, array $attributes, array $codeGroupings): bool
    {
        foreach ($attributes as $attribute) {
            foreach ($codeGroupings as $codeGroup) {
                foreach ($codeGroup as $curriculumSubject) {
                    if ($attribute == 'code') {
                        $text = str_replace(strtolower($curriculumSubject[$attribute]), '', $text);
                        $text = str_replace(strtoupper($curriculumSubject[$attribute]), '', $text);
                    }
                    elseif (!empty($curriculumSubject[$attribute])) {
                        $text = str_replace($curriculumSubject[$attribute], '', $text);
                    }
                }
            }
        }

        $text = trim($this->filterText($text));

        return empty($text);
    }

    /**
     * Prepares the data to be saved.
     * @return array
     */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        // External references are not in the table and as such won't be automatically prepared.
        $data['coordinators']    = Input::getIntArray('coordinators');
        $data['organizationIDs'] = Input::getIntArray('organizationIDs');
        $data['persons']         = Input::getIntArray('persons');
        $data['prerequisites']   = Input::getIntArray('prerequisites');
        $data['programIDs']      = Input::getIntArray('programIDs');
        $data['superordinates']  = Input::getIntArray('superordinates');

        // Because most values are imported this is the only item that is technically required.
        $this->validate($data, ['organizationIDs']);

        return $data;
    }

    /**
     * Method to retrieve updated prerequisite options after curriculum selection changes.
     *
     * @return  void
     */
    public function prerequisitesAjax(): void
    {
        if (!$this->checkToken('get', false)) {
            http_response_code(403);
            echo '';
            $this->app->close();
        }

        if (!$id = Input::getID()) {
            http_response_code(400);
            echo '';
            $this->app->close();
        }

        $options = '';
        $ranges  = Programs::programs(Input::getIntCollection('programIDs'));

        foreach (Helper::preOptions($id, $ranges) as $option) {
            $options .= "<option value='$option->value' $option->selected $option->disable>$option->text</option>";
        }

        echo $options;

        $this->app->close();
    }

    /**
     * @inheritDoc
     */
    public function postProcess(array $data): void
    {
        if (!$this->updateAssociations('subjectID', $data['id'], $data['organizationIDs'])) {
            Application::message('UPDATE_ASSOCIATION_FAILED', Application::WARNING);
        }

        if (!$this->assignments($data)) {
            Application::message('UPDATE_ASSIGNMENT_FAILED', Application::WARNING);
        }

        $this->updateSuperOrdinates($data);

        // Dependant on curricula entries.
        if (!$this->processPrerequisites($data['id'], $data['prerequisites'])) {
            Application::message('UPDATE_DEPENDENCY_FAILED', Application::WARNING);
        }

        /*if (!$this->processEvents($data))
        {
            Application::message('TBD', Application::WARNING);
        }*/
    }

    /**
     * Processes the subject prerequisites selected for the subject
     *
     * @param   int    $subjectID      the id of the subject to map prerequisites for
     * @param   array  $prerequisites  the prerequisites discovered during resolution
     *
     * @return bool
     */
    private function processPrerequisites(int $subjectID, array $prerequisites): bool
    {
        // Unmapped => impossible to create a dependency hierarchy
        if (!$subjectRanges = $this->ranges($subjectID)) {
            return true;
        }

        $programRanges = Programs::rows($subjectRanges);

        if ($prerequisites = array_filter($prerequisites) and !in_array(self::NONE, $prerequisites)) {
            $prerequisiteRanges = [];
            foreach ($prerequisites as $prerequisiteID) {
                $prerequisiteRanges = array_merge($prerequisiteRanges, $this->ranges($prerequisiteID));
            }

            foreach ($programRanges as $programRange) {

                // 'r' is for relevant
                if (!$rprRanges = $this->filterRanges($programRange, $prerequisiteRanges)) {
                    continue;
                }

                if (!$rsRanges = $this->filterRanges($programRange, $subjectRanges)) {
                    continue;
                }

                // Remove deprecated associations
                $query = DB::getQuery();
                $query->delete('#__organizer_prerequisites')
                    ->whereIn(DB::qn('subjectID'), Helper::curriculumIDs($rsRanges))
                    ->whereNotIn(DB::qn('prerequisiteID'), Helper::curriculumIDs($rprRanges));
                DB::setQuery($query);

                if (!DB::execute()) {
                    return false;
                }

                foreach ($rprRanges as $rprRange) {
                    foreach ($rsRanges as $rsRange) {
                        $data = ['subjectID' => $rsRange['id'], 'prerequisiteID' => $rprRange['id']];

                        $prerequisites = new Tables\Prerequisites();
                        if ($prerequisites->load($data)) {
                            continue;
                        }

                        if (!$prerequisites->save($data)) {
                            return false;
                        }
                    }
                }
            }

            $success = true;
        }
        else {
            $success = $this->removeDependency($subjectID, self::PRE);
        }

        return $success;
    }

    /**
     * @inheritDoc
     */
    public function processStub(SimpleXMLElement $XMLObject, int $organizationID, int $parentID): bool
    {
        if (!$lsfID = (string) (empty($XMLObject->modulid) ? $XMLObject->pordid : $XMLObject->modulid)) {
            return false;
        }

        $blocked = !empty($XMLObject->sperrmh) and strtolower((string) $XMLObject->sperrmh) === 'x';
        $validTitle = $this->validTitle($XMLObject);

        $subject = new Table();

        if (!$subject->load(['lsfID' => $lsfID])) {
            // There isn't one and shouldn't be one
            if ($blocked or !$validTitle) {
                return true;
            }

            $subject->lsfID = $lsfID;

            if (!$subject->store()) {
                return false;
            }
        }
        // There is one and shouldn't be one
        elseif ($blocked or !$validTitle) {
            return $this->delete($subject->id);
        }

        $this->checkAssociation($organizationID, 'subjectID', $subject->id);

        $curriculum = new Tables\Curricula();
        $this->checkCurriculum($curriculum, $parentID, 'subjectID', $subject->id);

        return $this->import($subject->id);
    }

    /**
     * Removes prerequisite associations for the given subject. No access checks => this is not directly
     * accessible and requires differing checks according to its calling context.
     *
     * @param   int  $subjectID  the subject id
     *
     * @return bool true on success, otherwise false
     */
    private function removeDependency(int $subjectID, int $direction): bool
    {
        if ($rangeIDs = Helper::curriculumIDs($this->ranges($subjectID))) {

            $query = DB::getQuery();
            $query->delete(DB::qn('#__organizer_prerequisites'));

            if ($direction) {
                $query->whereIn(DB::qn('subjectID'), $rangeIDs);
            }
            else {
                $query->whereIn(DB::qn('prerequisiteID'), $rangeIDs);
            }
            DB::setQuery($query);

            return DB::execute();
        }

        return true;
    }

    /**
     * Parses the prerequisites text and replaces module numbers with subject links
     *
     * @param   Table  $table  the subjects table object
     *
     * @return bool
     */
    private function resolve(Table $table): bool
    {
        // Subject is not associated with a program
        if (!$programRanges = Helper::programs($table->id)) {
            return ($this->removeDependency($table->id, self::PRE) and $this->removeDependency($table->id, self::POST));
        }

        // Ordered by length for faster in case short is a subset of long.
        $checkedAttributes = [
            'abbreviation_de',
            'abbreviation_en',
            'code',
            'fullName_de',
            'fullName_en',
            'name_de',
            'name_en'
        ];

        // Flag to be set should one of the attribute texts consist only of module information. => Text should be empty.
        $attributeChanged = false;

        $reqAttribs    = [
            'prerequisites_de',
            'prerequisites_en'
        ];
        $prerequisites = [];

        foreach ($reqAttribs as $attribute) {
            $originalText = $table->$attribute;
            $codes        = [];

            foreach (explode(' ', $this->filterText($originalText)) as $filteredText) {
                if (preg_match('/([A-Za-z0-9]{3,10})/', $filteredText)) {
                    $codes[$filteredText] = $filteredText;
                }
            }

            if (empty($codes)) {
                continue;
            }

            if ($dependencies = $this->verifyDependencies($codes, $programRanges)) {
                $prerequisites = $prerequisites + $dependencies;


                if ($this->onlyReferences($originalText, $checkedAttributes, $dependencies)) {
                    $table->$attribute = '';
                    $attributeChanged  = true;
                }
            }
        }

        if (!$this->saveDependencies($programRanges, $table->id, $prerequisites)) {
            return false;
        }

        if ($attributeChanged) {
            return $table->store();
        }

        return true;
    }

    /**
     * Removes various inconsistencies that have appeared in the object values over the years.
     *
     * @param   string  $text  the xml node as a string
     *
     * @return string
     */
    private function sanitizeText(string $text): string
    {
        // Gets rid of bullshit encoding from copy and paste from word
        $text = str_replace(chr(160), ' ', $text);
        $text = str_replace(chr(194) . chr(167), '&sect;', $text);
        $text = str_replace(chr(194) . chr(171), '&laquo;', $text);
        $text = str_replace(chr(194) . chr(187), '&raquo;', $text);
        $text = str_replace(chr(194), ' ', $text);
        $text = str_replace(chr(195) . chr(159), '&szlig;', $text);
        $text = str_replace(chr(226) . chr(128) . chr(162), '&bull;', $text);

        // Remove the formatted text tag
        $text = preg_replace('/<\/?[f|F]ormatted[t|T]ext>/', '', $text);

        // Remove non-self-closing tags with no content and unwanted self-closing tags
        $text = preg_replace('/<((?!br|col|link).)[a-z]*\s*\/>/', '', $text);

        // Replace non-blank spaces
        $text = preg_replace('/&nbsp;/', ' ', $text);

        // Replace windows return entity with <br>
        $text = preg_replace('/&#13;/', '<br>', $text);

        // Run iterative parsing for nested bullshit.
        do {
            $startText = $text;

            // Replace multiple whitespace characters with a single space
            $text = preg_replace('/\s+/', ' ', $text);

            // Replace non-blank spaces
            $text = ltrim($text);

            // Remove leading white space
            $text = ltrim($text);

            // Remove trailing white space
            $text = rtrim($text);

            // Replace remaining white space with an actual space to prevent errors from weird coding
            $text = preg_replace("/\s$/", ' ', $text);

            // Remove white space between closing and opening tags
            $text = preg_replace('/(<\/[^>]+>)\s*(<[^>]*>)/', "$1$2", $text);

            // Remove non-self closing tags containing only white space
            $text = preg_replace('/<[^\/>][^>]*>\s*<\/[^>]+>/', '', $text);
        }
        while ($text != $startText);

        return $text;
    }

    /**
     * Saves the dependencies to the prerequisites table
     *
     * @param   array  $programs      the programs that the schedule should be associated with
     * @param   int    $subjectID     the id of the subject being processed
     * @param   array  $dependencies  the subject dependencies
     *
     * @return bool
     */
    private function saveDependencies(array $programs, int $subjectID, array $dependencies): bool
    {
        $subjectRanges = $this->ranges($subjectID);

        foreach ($programs as $program) {
            // Program context filtered subject ranges
            $subjectRanges = $this->filterRanges($program, $subjectRanges);
            $subjectIDs    = Helper::curriculumIDs($subjectRanges);

            // Program context filtered dependency ranges
            $prerequisiteIDs = [];
            foreach ($dependencies as $dependency) {
                $prRanges        = $this->filterRanges($program, $dependency);
                $prerequisiteIDs = array_merge($prerequisiteIDs, Helper::curriculumIDs($prRanges));
            }

            $prerequisiteIDs = array_unique($prerequisiteIDs);

            // Delete any and all old prerequisites in case there are now fewer.
            if ($subjectIDs) {
                $query = DB::getQuery();
                $query->delete(DB::qn('#__organizer_prerequisites'))->whereIn(DB::qn('subjectID'), $subjectIDs);
                DB::setQuery($query);
                DB::execute();
            }

            foreach ($prerequisiteIDs as $prerequisiteID) {
                foreach ($subjectIDs as $subjectID) {
                    $table = new Tables\Prerequisites();
                    if (!$table->load(['prerequisiteID' => $prerequisiteID, 'subjectID' => $subjectID])) {
                        $table->prerequisiteID = $prerequisiteID;
                        $table->subjectID      = $subjectID;

                        if (!$table->store()) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Removes person associations for the given subject and role.
     *
     * @param   int  $subjectID  the subject id
     *
     * @return bool
     */
    private function unassign(int $subjectID): bool
    {
        $query = DB::getQuery();
        $query->delete(DB::qn('#__organizer_subject_persons'))
            ->where(DB::qn('subjectID') . ' = :subjectID')
            ->bind(':subjectID', $subjectID, ParameterType::INTEGER);

        DB::setQuery($query);

        return DB::execute();
    }

    /**
     * Checks for subjects with the given possible module number associated with to the same programs.
     *
     * @param   array  $codes   the possible code values used in the attribute text
     * @param   array  $ranges  the program ranges whose curricula contain the subject being processed
     *
     * @return array[]
     */
    private function verifyDependencies(array $codes, array $ranges): array
    {
        $aliased  = DB::qn(['s.id', 'c.id'], ['subjectID', 'curriculumID']);
        $bound    = [':programID AS programID'];
        $selected = DB::qn(['abbreviation_de', 'abbreviation_en', 'code', 'fullName_de', 'fullName_en', 'c.lft', 'c.rgt']);

        $query = DB::getQuery();
        $query->select(array_merge($aliased, $bound, $selected))
            ->from(DB::qn('#__organizer_subjects', 's'))
            ->innerJoin(DB::qn('#__organizer_curricula', 'c'), DB::qc('c.subjectID', 's.id'))
            ->where([DB::qn('s.code') . ' = :code', DB::qn('lft') . ' > :left', DB::qn('rgt') . ' < :right']);

        $subjects = [];
        foreach ($codes as $code) {
            $code = strtoupper($code);

            foreach ($ranges as $program) {

                $query
                    ->bind(':code', $code)
                    ->bind(':left', $program['lft'], ParameterType::INTEGER)
                    ->bind(':programID', $program['id'], ParameterType::INTEGER)
                    ->bind(':right', $program['rgt'], ParameterType::INTEGER);

                DB::setQuery($query);

                if (!$results = DB::loadAssocList('curriculumID')) {
                    continue;
                }

                if (!array_key_exists($code, $subjects)) {
                    $subjects[$code] = [];
                }

                $subjects[$code] += $results;
            }
        }

        return $subjects;
    }
}