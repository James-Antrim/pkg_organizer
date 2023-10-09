<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Exception;
use Organizer\Adapters\Database;
use Organizer\Helpers;
use Organizer\Helpers\OrganizerHelper;
use Organizer\Tables;
use SimpleXMLElement;

/**
 * Class which manages stored subject data.
 */
class Subject extends CurriculumResource
{
    use Associated;
    use SubOrdinate;

    private const COORDINATES = 1, TEACHES = 2;

    protected string $helper = 'Subjects';

    protected string $resource = 'subject';

    /**
     * Adds a Subject => Event association. No access checks => this is not directly accessible and requires
     * differing checks according to its calling context.
     *
     * @param int   $subjectID the id of the subject
     * @param array $eventIDs  the ids of the events
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
     * Associates subject curriculum dependencies.
     *
     * @param array $programRanges          the program ranges
     * @param array $prerequisiteRanges     the prerequisite ranges
     * @param array $subjectRanges          the subject ranges
     * @param bool  $pre                    whether or not the function is being called in the prerequisite context this
     *                                      influences how possible deprecated entries are detected.
     *
     * @return bool true on success, otherwise false
     */
    private function associate(array $programRanges, array $prerequisiteRanges, array $subjectRanges, bool $pre): bool
    {
        foreach ($programRanges as $programRange) {
            if (!$rprRanges = $this->filterRanges($programRange, $prerequisiteRanges)) {
                continue;
            }

            if (!$rsRanges = $this->filterRanges($programRange, $subjectRanges)) {
                continue;
            }

            // Remove deprecated associations
            $rprIDs = implode(',', Helpers\Subjects::filterIDs($rprRanges));
            $rsIDs  = implode(',', Helpers\Subjects::filterIDs($rsRanges));
            $query  = Database::getQuery();
            $query->delete('#__organizer_prerequisites');

            if ($pre) {
                $query->where("subjectID IN ($rsIDs)")->where("prerequisiteID NOT IN ($rprIDs)");
            } else {
                $query->where("prerequisiteID IN ($rsIDs)")->where("subjectID NOT IN ($rprIDs)");
            }

            Database::setQuery($query);

            if (!Database::execute()) {
                return false;
            }

            foreach ($rprRanges as $rprRange) {
                foreach ($rsRanges as $rsRange) {
                    $data          = ['subjectID' => $rsRange['id'], 'prerequisiteID' => $rprRange['id']];
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

        return true;
    }

    /**
     * Checks if the property should be displayed. Setting it to NULL if not.
     *
     * @param array  &$data     the form data
     * @param string  $property the property name
     *
     * @return void  can change the &$data value at the property name index
     */
    private function cleanStarProperty(array &$data, string $property)
    {
        if (!isset($data[$property])) {
            $data[$property] = 'NULL';

            return;
        }

        $value = (int) $data[$property];
        if ($value >= 3) {
            $data[$property] = 3;
        } elseif ($value >= 0) {
            $data[$property] = $value;
        } else {
            $data[$property] = null;
        }
    }

    /**
     * Filters subject ranges to those relevant to a given program range.
     *
     * @param array $programRange  the program range being iterated
     * @param array $subjectRanges the ranges for the given subject
     *
     * @return array[] the relevant subject ranges
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
     * @inheritDoc
     */
    public function importSingle(int $resourceID): bool
    {
        $table = new Tables\Subjects();

        if (!$table->load($resourceID) or empty($table->lsfID)) {
            return false;
        }

        try {
            $client = new Helpers\LSF();
        } catch (Exception $exception) {
            Helpers\OrganizerHelper::message('ORGANIZER_LSF_CLIENT_FAILED', 'error');

            return false;
        }

        $response = $client->getModule($table->lsfID);

        // Invalid response
        if (empty($response->modul)) {
            $message = sprintf(Helpers\Languages::_('ORGANIZER_LSF_RESPONSE_EMPTY'), $table->lsfID);
            OrganizerHelper::message($message, 'notice');

            return $this->deleteSingle($table->id);
        }

        $subject = $response->modul;

        if (!$this->validTitle($subject)) {
            $message = sprintf(Helpers\Languages::_('ORGANIZER_IMPORT_TITLE_INVALID'), $table->lsfID);
            OrganizerHelper::message($message, 'error');

            return $this->deleteSingle($table->id);
        }

        $tag           = Helpers\Languages::getTag();
        $titleProperty = "titel$tag";
        $title         = $subject->$titleProperty;

        // Suppressed
        if (!empty($subject->sperrmh) and strtolower((string) $subject->sperrmh) === 'x') {
            $message = sprintf(Helpers\Languages::_('ORGANIZER_SUBJECT_SUPPRESSED'), $title, $table->lsfID);
            OrganizerHelper::message($message, 'notice');

            return $this->deleteSingle($table->id);
        }

        if (!$this->setPersons($table->id, $subject)) {
            OrganizerHelper::message('ORGANIZER_SAVE_FAIL', 'error');

            return false;
        }

        $this->setNameAttributes($table, $subject);

        Helpers\SubjectsLSF::processAttributes($table, $subject);

        if (!$table->store()) {
            return false;
        }

        return $this->resolve($table->id);
    }

    /**
     * Processes the events to be associated with the subject
     *
     * @param array &$data the post data
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
     * Processes the persons selected for the subject
     *
     * @param array $data the post data
     *
     * @return bool  true on success, otherwise false
     */
    private function processPersons(array $data): bool
    {
        // More efficient to remove all subject persons associations for the subject than iterate the persons table
        if (!$this->removePersons($data['id'])) {
            return false;
        }

        $coordinatorsSet = !empty($data['coordinators']);
        $personsSet      = !empty($data['persons']);

        if (!$coordinatorsSet and !$personsSet) {
            return true;
        }

        if ($coordinatorsSet and $persons = array_filter($data['coordinators'])) {
            foreach ($persons as $personID) {
                $spData = ['personID' => $personID, 'role' => self::COORDINATES, 'subjectID' => $data['id']];
                $table  = new Tables\SubjectPersons();

                if (!$table->save($spData)) {
                    return false;
                }
            }

        }

        if ($personsSet and $persons = array_filter($data['persons'])) {
            foreach ($persons as $personID) {
                $spData = ['personID' => $personID, 'role' => self::TEACHES, 'subjectID' => $data['id']];
                $table  = new Tables\SubjectPersons();

                if (!$table->save($spData)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Processes the subject prerequisites selected for the subject
     *
     * @param array $data the post data
     *
     * @return bool  true on success, otherwise false
     */
    private function processPrerequisites(array $data): bool
    {
        $subjectID = $data['id'];

        if (!$subjectRanges = $this->getRanges($subjectID)) {
            return true;
        }

        $programRanges = Helpers\Programs::getRanges($subjectRanges);

        $preRequisites = array_filter($data['prerequisites']);
        if (!empty($preRequisites) and array_search(self::NONE, $preRequisites) === false) {
            $prerequisiteRanges = [];
            foreach ($preRequisites as $preRequisiteID) {
                $prerequisiteRanges = array_merge($prerequisiteRanges, $this->getRanges($preRequisiteID));
            }

            $success = $this->associate($programRanges, $prerequisiteRanges, $subjectRanges, true);
        } else {
            $success = $this->removePreRequisites($subjectID);
        }

        return $success;
    }

    /**
     * Creates a subject and a curricula table entries as necessary.
     *
     * @param SimpleXMLElement $XMLObject      a SimpleXML object containing rudimentary resource data
     * @param int              $organizationID the id of the organization with which the resource is associated
     * @param int              $parentID       the  id of the parent entry in the curricula table
     *
     * @return bool  true on success, otherwise false
     */
    public function processResource(SimpleXMLElement $XMLObject, int $organizationID, int $parentID): bool
    {
        $lsfID = (string) (empty($XMLObject->modulid) ? $XMLObject->pordid : $XMLObject->modulid);
        if (empty($lsfID)) {
            return false;
        }

        $blocked = !empty($XMLObject->sperrmh) and strtolower((string) $XMLObject->sperrmh) == 'x';
        $validTitle = $this->validTitle($XMLObject);

        $subject = new Tables\Subjects();

        if (!$subject->load(['lsfID' => $lsfID])) {
            // There isn't one and shouldn't be one
            if ($blocked or !$validTitle) {
                return true;
            }

            $subject->lsfID = $lsfID;

            if (!$subject->store()) {
                return false;
            }
        } elseif ($blocked or !$validTitle) {
            return $this->deleteSingle($subject->id);
        }

        $curricula = new Tables\Curricula();

        if (!$curricula->load(['parentID' => $parentID, 'subjectID' => $subject->id])) {
            $range = [
                'parentID' => $parentID,
                'subjectID' => $subject->id,
                'ordering' => $this->getOrdering($parentID, $subject->id)
            ];

            if (!$this->shiftUp($parentID, $range['ordering'])) {
                return false;
            }

            if (!$this->addRange($range)) {
                return false;
            }

            $curricula->load(['parentID' => $parentID, 'poolID' => $subject->id]);
        }

        $association = new Tables\Associations();
        if (!$association->load(['organizationID' => $organizationID, 'subjectID' => $subject->id])) {
            $association->save(['organizationID' => $organizationID, 'subjectID' => $subject->id]);
        }

        return $this->importSingle($subject->id);
    }

    /**
     * Removes pre- & postrequisite associations for the given subject. No access checks => this is not directly
     * accessible and requires differing checks according to its calling context.
     *
     * @param int $subjectID the subject id
     *
     * @return bool true on success, otherwise false
     */
    private function removeDependencies(int $subjectID): bool
    {
        return ($this->removePreRequisites($subjectID) and $this->removePostRequisites($subjectID));
    }

    /**
     * Removes planSubject associations for the given subject. No access checks => this is not directly accessible and
     * requires differing checks according to its calling context.
     *
     * @param int $subjectID the subject id
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
     * Removes person associations for the given subject and role. No access checks => this is not directly
     * accessible and requires differing checks according to its calling context.
     *
     * @param int $subjectID the subject id
     * @param int $role      the person role
     *
     * @return bool
     */
    private function removePersons(int $subjectID, int $role = 0): bool
    {
        $query = Database::getQuery();
        $query->delete('#__organizer_subject_persons')->where("subjectID = $subjectID");

        if ($role) {
            $query->where("role = $role");
        }

        Database::setQuery($query);

        return Database::execute();
    }

    /**
     * Removes prerequisite associations for the given subject. No access checks => this is not directly
     * accessible and requires differing checks according to its calling context.
     *
     * @param int $subjectID the subject id
     *
     * @return bool true on success, otherwise false
     */
    private function removePreRequisites(int $subjectID): bool
    {
        if ($rangeIDs = Helpers\Subjects::filterIDs($this->getRanges($subjectID))) {
            $rangeIDString = implode(',', $rangeIDs);

            $query = Database::getQuery();
            $query->delete('#__organizer_prerequisites')->where("subjectID IN ($rangeIDString)");
            Database::setQuery($query);

            return Database::execute();
        }

        return true;
    }

    /**
     * Removes postrequisite associations for the given subject. No access checks => this is not directly
     * accessible and requires differing checks according to its calling context.
     *
     * @param int $subjectID the subject id
     *
     * @return bool true on success, otherwise false
     */
    private function removePostRequisites(int $subjectID): bool
    {
        if ($rangeIDs = Helpers\Subjects::filterIDs($this->getRanges($subjectID))) {
            $rangeIDString = implode(',', $rangeIDs);

            $query = Database::getQuery();
            $query->delete('#__organizer_prerequisites')->where("prerequisiteID IN ($rangeIDString)");
            Database::setQuery($query);

            return Database::execute();
        }

        return true;
    }

    /**
     * Parses the prerequisites text and replaces subject references with links to the subjects
     *
     * @param int $subjectID the id of the subject being processed
     *
     * @return bool true on success, otherwise false
     */
    private function resolve(int $subjectID): bool
    {
        $table = new Tables\Subjects();

        // Entry doesn't exist. Should not occur.
        if (!$table->load($subjectID)) {
            return false;
        }

        // Subject is not associated with a program
        if (!$programRanges = Helpers\Subjects::getPrograms($subjectID)) {
            return $this->removeDependencies($subjectID);
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
            $originalText   = $table->$attribute;
            $potentialCodes = [];

            foreach (explode(' ', Helpers\SubjectsLSF::sanitizeText($originalText)) as $sanitizedText) {
                if (preg_match('/([A-Za-z0-9]{3,10})/', $sanitizedText)) {
                    $potentialCodes[$sanitizedText] = $sanitizedText;
                }
            }

            if (empty($potentialCodes)) {
                continue;
            }

            if ($dependencies = $this->verifyDependencies($potentialCodes, $programRanges)) {
                $prerequisites = $prerequisites + $dependencies;

                $emptyAttribute = Helpers\SubjectsLSF::checkContents($originalText, $checkedAttributes, $dependencies);

                if ($emptyAttribute) {
                    $table->$attribute = '';
                    $attributeChanged  = true;
                }
            }
        }

        if (!$this->saveDependencies($programRanges, $subjectID, $prerequisites, 'pre')) {
            return false;
        }

        if ($attributeChanged) {
            return $table->store();
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function save(array $data = [])
    {
        $data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;

        $this->authorize();

        $data['creditPoints'] = (int) $data['creditPoints'];

        $starProperties = ['expertise', 'selfCompetence', 'methodCompetence', 'socialCompetence'];
        foreach ($starProperties as $property) {
            $this->cleanStarProperty($data, $property);
        }

        $table = new Tables\Subjects();

        if (!$table->save($data)) {
            return false;
        }

        $data['id'] = $table->id;

        if (!empty($data['organizationIDs']) and !$this->updateAssociations($data['id'], $data['organizationIDs'])) {
            return false;
        }

        if (!$this->processPersons($data)) {
            return false;
        }

        $superOrdinates = $this->getSuperOrdinates($data);

        if (!$this->addNew($data, $superOrdinates)) {
            return false;
        }

        $this->removeDeprecated($table->id, $superOrdinates);

        // Dependant on curricula entries.
        if (!$this->processPrerequisites($data)) {
            return false;
        }

        /*if (!$this->processEvents($data))
        {
            return false;
        }*/

        return $table->id;
    }

    /**
     * Saves the dependencies to the prerequisites table
     *
     * @param array  $programs     the programs that the schedule should be associated with
     * @param int    $subjectID    the id of the subject being processed
     * @param array  $dependencies the subject dependencies
     * @param string $type         the type (direction) of dependency: pre|post
     *
     * @return bool
     */
    private function saveDependencies(array $programs, int $subjectID, array $dependencies, string $type): bool
    {
        $subjectRanges = $this->getRanges($subjectID);

        foreach ($programs as $program) {
            // Program context filtered subject ranges
            $fsRanges   = $this->filterRanges($program, $subjectRanges);
            $fsRangeIDs = Helpers\Subjects::filterIDs($fsRanges);

            // Program context filtered dependency ranges
            $fdRangeIDs = [];
            foreach ($dependencies as $dependency) {
                $fdRanges   = $this->filterRanges($program, $dependency);
                $fdRangeIDs = array_merge($fdRangeIDs, Helpers\Subjects::filterIDs($fdRanges));
            }

            $fdRangeIDs = array_unique($fdRangeIDs);

            if ($type == 'pre') {
                $success = $this->savePrerequisites($fdRangeIDs, $fsRangeIDs);
            } else {
                $success = $this->savePrerequisites($fsRangeIDs, $fdRangeIDs);
            }

            if (!$success) {
                return false;
            }
        }

        return true;
    }

    /**
     * Saves the prerequisite relation.
     *
     * @param array $prerequisiteIDs ids for prerequisite subject entries in the program curriculum context
     * @param array $subjectIDs      ids for subject entries in the program curriculum context
     *
     * @return bool true on success otherwise false
     */
    private function savePrerequisites(array $prerequisiteIDs, array $subjectIDs): bool
    {
        // Delete any and all old prerequisites in case there are now fewer.
        if ($subjectIDs) {
            $deleteQuery = Database::getQuery();
            $deleteQuery->delete('#__organizer_prerequisites')->where('subjectID IN (' . implode(',',
                    $subjectIDs) . ')');
            Database::setQuery($deleteQuery);
            Database::execute();
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

        return true;
    }

    /**
     * Creates an association between persons, subjects and their roles for that subject.
     *
     * @param int              $subjectID  the id of the subject
     * @param SimpleXMLElement $dataObject an object containing the lsf response
     *
     * @return bool  true on success, otherwise false
     */
    private function setPersons(int $subjectID, SimpleXMLElement $dataObject): bool
    {
        $coordinators = $dataObject->xpath('//verantwortliche');
        $persons      = $dataObject->xpath('//dozent');

        $this->removePersons($subjectID);

        if (empty($coordinators) and empty($persons)) {
            return true;
        }

        if (!$this->setPersonsByRoles($subjectID, $coordinators, self::COORDINATES)) {
            return false;
        }

        if (!$this->setPersonsByRoles($subjectID, $persons, self::TEACHES)) {
            return false;
        }

        return true;
    }

    /**
     * Sets subject persons by their role for the subject
     *
     * @param int   $subjectID the subject's id
     * @param array $persons   an array containing information about the subject's persons
     * @param int   $role      the person's role
     *
     * @return bool  true on success, otherwise false
     */
    private function setPersonsByRoles(int $subjectID, array $persons, int $role): bool
    {
        $subjectModel = new Subject();

        if (!$subjectModel->removePersons($subjectID, $role)) {
            return false;
        }

        if (empty($persons)) {
            return true;
        }

        $surnameAttribute  = $role == self::COORDINATES ? 'nachname' : 'personal.nachname';
        $forenameAttribute = $role == self::COORDINATES ? 'vorname' : 'personal.vorname';

        foreach ($persons as $person) {
            $personData             = [];
            $personData['surname']  = trim((string) $person->personinfo->$surnameAttribute);
            $personData['username'] = trim((string) $person->hgnr);

            if (empty($personData['surname']) or empty($personData['username'])) {
                continue;
            }

            $loadCriteria           = [];
            $loadCriteria[]         = ['username' => $personData['username']];
            $personData['forename'] = (string) $person->personinfo->$forenameAttribute;

            if (!empty($personData['forename'])) {
                $loadCriteria[] = ['surname' => $personData['surname'], 'forename' => $personData['forename']];
            }

            $personTable = new Tables\Persons();
            $loaded      = false;

            foreach ($loadCriteria as $criteria) {
                if ($personTable->load($criteria)) {
                    $loaded = true;
                    break;
                }
            }

            if (!$loaded and !$personTable->save($personData)) {
                return false;
            }

            $spData  = ['personID' => $personTable->id, 'role' => $role, 'subjectID' => $subjectID];
            $spTable = new Tables\SubjectPersons();

            if (!$spTable->save($spData)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks for subjects with the given possible module number associated with to the same programs.
     *
     * @param array $potentialCodes the possible code values used in the attribute text
     * @param array $programRanges  the program ranges whose curricula contain the subject being processed
     *
     * @return array[] the subject information for subjects with dependencies
     */
    private function verifyDependencies(array $potentialCodes, array $programRanges): array
    {
        $select = 's.id AS subjectID, code, ';
        $select .= 'abbreviation_de, fullName_de, abbreviation_en, fullName_en, ';
        $select .= 'c.id AS curriculumID, c.lft, c.rgt, ';

        $query = Database::getQuery();
        $query->from('#__organizer_subjects AS s')
            ->innerJoin('#__organizer_curricula AS c ON c.subjectID = s.id');

        $subjects = [];
        foreach ($potentialCodes as $possibleModuleNumber) {
            $possibleModuleNumber = strtoupper($possibleModuleNumber);

            foreach ($programRanges as $program) {
                $query->clear('select')->clear('where');

                $query->select($select . "{$program['id']} AS programID")
                    ->where("lft > {$program['lft']} AND rgt < {$program['rgt']}")
                    ->where("s.code = '$possibleModuleNumber'");
                Database::setQuery($query);

                if (!$curriculumSubjects = Database::loadAssocList('curriculumID')) {
                    continue;
                }

                if (!array_key_exists($possibleModuleNumber, $subjects)) {
                    $subjects[$possibleModuleNumber] = [];
                }

                $subjects[$possibleModuleNumber] += $curriculumSubjects;
            }
        }

        return $subjects;
    }
}
