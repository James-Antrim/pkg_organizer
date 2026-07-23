<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

use Joomla\Database\ParameterType;
use stdClass;
use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input, Text, User};
use THM\Organizer\Tables\{Prerequisites, SubjectMethods, SubjectPersons, Subjects as Table};

/**
 * Provides general functions for subject access checks, data retrieval and display.
 */
class Subjects extends Curricula implements Subordinate
{
    private const POST = 0, PRE = 1;

    protected static string $resource = 'subject';

    /**
     * Assigns personell from the import, deleting existing assignments for the subject.
     *
     * @param int   $subjectID
     * @param array $coordinators the coordinator data (structure tbd)
     * @param array $teachers     the teacher data (structure tbd)
     * @return void
     */
    private static function assignments(int $subjectID, array $coordinators, array $teachers): void
    {
        $coordinators = array_filter($coordinators);
        $teachers     = array_filter($teachers);

        if ((!$coordinators and !$teachers) or !self::unassign($subjectID)) {
            return;
        }

        if ($coordinators) {
            foreach ($coordinators as $coordinator) {
                $coordinatorID = Persons::insert($coordinator->username, $coordinator->surnames, $coordinator->forenames, $coordinator->titles);
                $spData        = ['personID' => $coordinatorID, 'role' => Persons::COORDINATES, 'subjectID' => $subjectID];
                $table         = new SubjectPersons();
                $table->save($spData);
            }

        }

        if ($teachers) {
            foreach ($teachers as $teacher) {
                $teacherID = Persons::insert($teacher->username, $teacher->surnames, $teacher->forenames, $teacher->titles);
                $spData    = ['personID' => $teacherID, 'role' => Persons::TEACHES, 'subjectID' => $subjectID];
                $table     = new SubjectPersons();
                $table->save($spData);
            }
        }
    }

    /**
     * Resolves a potential text to a yes or no whether bonus points can be awarded for this subject.
     * @param string $bonusPoints
     * @return int
     */
    private static function bonusPoints(string $bonusPoints): int
    {
        if (empty($bonusPoints)) {
            return 0;
        }

        // Remove tags and indiscriminate left spacing then standardize as lower for comparisons.
        $bonusPoints = strtolower(trim(strip_tags($bonusPoints)));

        if (empty($bonusPoints) or str_contains($bonusPoints, 'nein') or str_contains($bonusPoints, 'kein') or $bonusPoints === '-') {
            return 0;
        }

        if (str_contains($bonusPoints, 'ja') or $bonusPoints === '1') {
            return 1;
        }

        //Explanatory text => implied no
        if (str_starts_with($bonusPoints, 'bonus')) {
            return 0;
        }

        //Explanatory text for exam prerequisites => implied error => no
        if (str_starts_with($bonusPoints, 'prüfungsvorleistung')) {
            return 0;
        }

        return 1;
    }

    /**
     * Checks for best fit values for credit points, hours of investiture and the proportion of hours in independent study and
     * presence.
     *
     * @param stdClass $resource an object containing resource data
     * @param Table    $subject  the subjects table
     *
     * @return void
     */
    private static function calculated(stdClass $resource, Table $subject): void
    {
        $subject->creditPoints = (int) $resource->CreditPoints ?? 0;
        $subject->expenditure  = (int) $resource->modulDetails->Arbeitsaufwand->de ?? $subject->creditPoints * 30;
        $subject->sws          = (int) $resource->SWS ?? 0;

        $present     = (int) $resource->modulDetails->Präsenzzeit->de ?? 0;
        $independent = (int) $resource->modulDetails->Selbststudium->de ?? 0;

        if (($independent + $present) === $subject->expenditure) {
            $subject->independent = $independent;
            $subject->present     = $present;
            return;
        }

        if ($present and $present < $subject->expenditure) {
            $subject->independent = $subject->expenditure - $present;
            $subject->present     = $present;
            return;
        }

        if ($independent and $independent < $subject->expenditure) {
            $subject->independent = $independent;
            $subject->present     = $subject->expenditure - $independent;
            return;
        }

        $subject->present     = 0;
        $subject->independent = 0;
    }

    /**
     * Check if user one of the subject's coordinators.
     *
     * @param int $subjectID the optional id of the subject
     * @param int $personID  the optional id of the person entry, defaults to current user
     *
     * @return bool true if the user is a coordinator, otherwise false
     */
    public static function coordinates(int $subjectID = 0, int $personID = 0): bool
    {
        if (!$personID = $personID ?: Persons::resolveUser(User::id())) {
            return false;
        }

        $coordinates = Persons::COORDINATES;
        $query       = DB::query();
        $query->select('COUNT(*)')->from(DB::qn('#__organizer_subject_persons'))
            ->where(DB::qn('personID') . ' = :personID')->bind(':personID', $personID, ParameterType::INTEGER)
            ->where(DB::qn('role') . ' = :coordinates')->bind(':coordinates', $coordinates, ParameterType::INTEGER);

        if ($subjectID) {
            $query->where(DB::qn('subjectID') . ' = :subjectID')->bind(':subjectID', $subjectID, ParameterType::INTEGER);
        }

        DB::set($query);

        return DB::bool();
    }

    /**
     * Parses the prerequisites text and replaces module numbers with subject links.
     * @param Table $table the subjects table object
     * @return void
     */
    private static function dependencies(Table $table): void
    {
        // Subject is not associated with a program
        if (!$programRanges = self::programs($table->id)) {
            self::removeDependencies($table->id, self::PRE);
            self::removeDependencies($table->id, self::POST);
            return;
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

        $prerequisites = [];

        $originalText = $table->prerequisites_de;
        $codes        = [];

        foreach (explode(' ', self::filterText($originalText)) as $filteredText) {
            if (preg_match('/([A-Za-z0-9]{3,10})/', $filteredText)) {
                $codes[$filteredText] = $filteredText;
            }
        }

        if (empty($codes)) {
            return;
        }

        if ($dependencies = self::verifyDependencies($codes, $programRanges)) {
            $prerequisites = $prerequisites + $dependencies;


            if (self::onlyReferences($originalText, $checkedAttributes, $dependencies)) {
                $table->prerequisites_de = '';
                $table->prerequisites_en = '';
            }
        }

        if (!self::saveDependencies($programRanges, $table->id, $prerequisites)) {
            return;
        }

        $table->store();
    }

    /**
     * Retrieves the event ID associated with the subject.
     *
     * @param int $subjectID the id of the referencing subject
     *
     * @return int the id of the referenced event
     */
    private static function eventID(int $subjectID): int
    {
        $query = DB::query();
        $query->select(DB::qn('eventID'))->from(DB::qn('#__organizer_subject_events'))
            ->where(DB::qn('subjectID') . ' = :subjectID')->bind(':subjectID', $subjectID, ParameterType::INTEGER);
        DB::set($query);

        return DB::integer();
    }

    /**
     * Retrieves the left and right boundaries of the nested program or pool
     * @return array|array[]
     */
    private static function filterRanges(): array
    {
        if (!$programRanges = Programs::rows(Input::integer('programID'))) {
            return [];
        }

        if ($poolRanges = Pools::rows(Input::integer('poolID'))
            and self::included($poolRanges, $programRanges)) {
            return $poolRanges;
        }

        return $programRanges;
    }

    /**
     * Filters text of excess white space, HTML tags, and irrelevant characters to reduce the parse load during pre- & post- requisite resolution.
     * @param string $text the text to be processed
     * @return string
     */
    private static function filterText(string $text): string
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
     * Retrieves the HISinOne system id of the subject.
     *
     * @param int $subjectID
     *
     * @return int
     */
    public static function HISinOneID(int $subjectID): int
    {
        $subject = new Table();
        if ($subject->load($subjectID)) {
            return $subject->HISinOneID ?: 0;
        }

        return 0;
    }

    /**
     * Resolves the textual representation of the distribution of sws to methods into a normalized database entry based version.
     * @param int    $subjectID
     * @param string $text the text containing method descriptions
     * @param int    $sws  the summary sws for the subject
     * @return void
     */
    private static function methods(int $subjectID, string $text, int $sws): void
    {
        if (preg_match_all('/(\d+) sws ([\p{L}\/ ]+)/iu', $text, $matches)) {
            self::removeMethods($subjectID);
            foreach (self::resolveMethods($matches[2]) as $index => $methodID) {
                $subjectMethod            = new SubjectMethods();
                $subjectMethod->methodID  = $methodID;
                $subjectMethod->subjectID = $subjectID;
                $subjectMethod->sws       = $matches[1][$index];
                $subjectMethod->store();
            }
        }
        if (preg_match_all('/([\p{L}\/ ]+) (\d+) sws/iu', $text, $matches)) {
            self::removeMethods($subjectID);
            foreach (self::resolveMethods($matches[1]) as $index => $methodID) {
                $subjectMethod            = new SubjectMethods();
                $subjectMethod->methodID  = $methodID;
                $subjectMethod->subjectID = $subjectID;
                $subjectMethod->sws       = $matches[2][$index];
                $subjectMethod->store();
            }
        }
        if (preg_match_all('/([\p{L}\/ ]+)/iu', $text, $matches)) {
            self::removeMethods($subjectID);
            foreach (self::resolveMethods([$text]) as $methodID) {
                $subjectMethod            = new SubjectMethods();
                $subjectMethod->methodID  = $methodID;
                $subjectMethod->subjectID = $subjectID;
                $subjectMethod->sws       = $sws;
                $subjectMethod->store();
            }
        }
    }

    /**
     * Retrieves the subject name
     *
     * @param int  $resourceID the table id for the subject
     * @param bool $withNumber whether to integrate the subject code directly into the name
     *
     * @return string the subject name
     */
    public static function name(int $resourceID = 0, bool $withNumber = false): string
    {
        if (!$resourceID = $resourceID ?: Input::id()) {
            return '';
        }

        $query = DB::query();
        $tag   = Application::tag();

        $select = DB::qn(["abbreviation_$tag", 'code', "fullName_$tag"], ['abbreviation', 'subjectNo', 'name']);
        $query->select($select)->from(DB::qn('#__organizer_subjects'))
            ->where(DB::qn('id') . ' = :subjectID')->bind(':subjectID', $resourceID, ParameterType::INTEGER);
        DB::set($query);

        if (!$names = DB::array()) {
            return '';
        }

        $suffix = '';

        if ($withNumber and !empty($names['subjectNo'])) {
            $suffix .= " ({$names['subjectNo']})";
        }

        if ($names['name']) {
            return $names['name'] . $suffix;
        }

        return $names['abbreviation'] . $suffix;
    }

    /**
     * Checks whether the text only consists of references to pre- & post- requisites.
     *
     * @param string $text          the text to be checked
     * @param array  $attributes    the attributes whose values are considered to be human-readable references
     * @param array  $codeGroupings array code (module number) => [curriculumID => subject information]
     *
     * @return bool
     */
    private static function onlyReferences(string $text, array $attributes, array $codeGroupings): bool
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

        $text = trim(self::filterText($text));

        return empty($text);
    }

    /** @inheritDoc */
    public static function options(): array
    {
        $options = [];
        foreach (self::resources() as $subject) {
            $options[] = HTML::option($subject['id'], $subject['name']);
        }

        return $options;
    }

    /**
     * Checks whether the pool is subordinate to the selected program
     *
     * @param array $poolBoundaries    the pool's left and right values
     * @param array $programBoundaries the program's left and right values
     *
     * @return bool  true if the pool is subordinate to the program,
     *                   otherwise false
     */
    public static function included(array $poolBoundaries, array $programBoundaries): bool
    {
        $first = $poolBoundaries[0];
        $last  = end($poolBoundaries);

        $leftValid  = $first['lft'] > $programBoundaries[0]['lft'];
        $rightValid = $last['rgt'] < $programBoundaries[0]['rgt'];
        if ($leftValid and $rightValid) {
            return true;
        }

        return false;
    }

    /**
     * Gets an array modeling the attributes of the resource.
     *
     * @param int $subjectID the id of the subject
     *
     * @return array
     */
    public static function load(int $subjectID): array
    {
        $table = new Table();

        if (!$table->load($subjectID)) {
            return [];
        }

        $eventID         = Subjects::eventID($subjectID);
        $fieldID         = $table->fieldID ?: 0;
        $organizationIDs = self::organizationIDs($table->id);
        $organizationID  = $organizationIDs ? $organizationIDs[0] : 0;
        $tag             = Application::tag();

        return [
            'abbreviation' => $table->{"abbreviation_$tag"},
            'bgColor' => Fields::color($fieldID, $organizationID),
            'creditPoints' => $table->creditPoints,
            'eventID' => $eventID,
            'field' => $fieldID ? Fields::name($fieldID) : '',
            'fieldID' => $table->fieldID,
            'id' => $table->id,
            'moduleNo' => $table->code,
            'name' => $table->{"fullName_$tag"}
        ];
    }

    /**
     * Retrieves the persons associated with a given subject and their respective roles for it.
     *
     * @param int $subjectID the id of the subject with which the persons must be associated
     * @param int $roleID    the role to be filtered against default none
     *
     * @return array the persons associated with the subject, empty if none were found.
     */
    public static function persons(int $subjectID, int $roleID = 0): array
    {
        $query = DB::query();
        $query->select(DB::qn(['p.id', 'p.surname', 'p.forename', 'p.title', 'sp.role']))
            ->from(DB::qn('#__organizer_persons', 'p'))
            ->innerJoin(DB::qn('#__organizer_subject_persons', 'sp'), DB::qc('sp.personID', 'p.id'))
            ->where(DB::qn('sp.subjectID') . ' = :subjectID')->bind(':subjectID', $subjectID, ParameterType::INTEGER);

        if ($roleID) {
            $query->where(DB::qn('sp.role') . ' = :roleID')->bind(':roleID', $roleID, ParameterType::INTEGER);
        }

        DB::set($query);

        if (!$results = DB::arrays()) {
            return [];
        }

        $persons = [];
        foreach ($results as $person) {
            $forename = empty($person['forename']) ? '' : $person['forename'];
            $fullName = $person['surname'];
            $fullName .= empty($forename) ? '' : ", {$person['forename']}";
            if (empty($persons[$person['id']])) {
                $person['forename'] = $forename;
                $person['title']    = empty($person['title']) ? '' : $person['title'];
                $person['role']     = [$person['role'] => $person['role']];
                $persons[$fullName] = $person;
                continue;
            }

            $persons[$person['id']]['role'] = [$person['role'] => $person['role']];
        }

        Persons::sortByRole($persons);
        Persons::sortByName($persons);

        return $persons;
    }

    /**
     * Looks up the names of the pools associated with the subject
     *
     * @param int $subjectID the id of the (plan) subject
     *
     * @return array|array[] the associated program names
     */
    public static function pools(int $subjectID): array
    {
        return Pools::rows(self::rows($subjectID));
    }

    /**
     * Retrieves the ids of subjects registered as prerequisites for a given subject
     *
     * @param int $subjectID the id of the subject
     *
     * @return int[] the associated prerequisites
     */
    public static function postrequisites(int $subjectID): array
    {
        return self::requisites($subjectID, 'post');
    }

    /**
     * Retrieves the ids of subjects registered as prerequisites for a given subject
     *
     * @param int $subjectID the id of the subject
     *
     * @return int[] the associated prerequisites
     */
    public static function prerequisites(int $subjectID): array
    {
        return self::requisites($subjectID, 'pre');
    }

    /**
     * Retrieves a list of options for choosing superordinate entries in the curriculum hierarchy.
     *
     * @param int   $subjectID the id of the subject for which the form is being displayed
     * @param array $ranges    the rows for programs selected in the form, or already mapped
     *
     * @return stdClass[] the superordinate resource options
     */
    public static function preOptions(int $subjectID, array $ranges): array
    {
        $default           = HTML::option(-1, Text::_('NO_PREREQUISITES'));
        $default->disable  = '';
        $default->selected = '';

        if (!$subjectID or !$ranges) {
            return [$default];
        }

        $addContext = count($ranges) > 1;
        $values     = [];

        foreach ($ranges as $pRange) {
            $pName = $addContext ? '(' . Programs::name($pRange['programID']) . ')' : '';
            foreach (Programs::subjects($pRange['programID']) as $sRange) {
                $value = $sRange['subjectID'];

                if ($value === $subjectID or !$text = Subjects::getFullName($value)) {
                    continue;
                }

                if (empty($values[$value])) {
                    $values[$value] = [
                        'text' => $text,
                        'programs' => [$pRange['programID'] => $pName]
                    ];
                }
                else {
                    $values[$value]['programs'][$pRange['programID']] = $pName;
                }
            }
        }

        $existing = self::requisites($subjectID, 'pre');

        foreach ($values as $value => $data) {
            $text = $data['text'];
            if ($addContext) {
                $text .= ' ';
                $text .= (count($data['programs']) > 1) ? '(' . Text::_('MULTIPLE_PROGRAMS') . ')' : reset($data['programs']);
            }

            $option           = HTML::option($value, $text);
            $option->disable  = '';
            $option->selected = in_array($value, $existing) ? 'selected' : '';
            $options[$text]   = $option;
        }

        ksort($options);

        return $options;
    }

    /**
     * Filters subject ranges to those relevant to the given program contexts.
     *
     * @param array $programRange  the program range being iterated
     * @param array $subjectRanges the ranges for the given subject
     *
     * @return array[]
     */
    public static function relevantRanges(array $programRange, array $subjectRanges): array
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
     * Removes prerequisite associations for the given subject.
     *
     * @param int  $subjectID the subject id
     * @param bool $direction true if post-requisite dependencies should be removed
     * @return bool
     */
    public static function removeDependencies(int $subjectID, bool $direction): bool
    {
        if ($rangeIDs = self::curriculumIDs(self::rows($subjectID))) {

            $query = DB::query();
            $query->delete(DB::qn('#__organizer_prerequisites'));

            if ($direction) {
                $query->whereIn(DB::qn('subjectID'), $rangeIDs);
            }
            else {
                $query->whereIn(DB::qn('prerequisiteID'), $rangeIDs);
            }
            DB::set($query);

            return DB::execute();
        }

        return true;
    }

    /**
     * Removes method associations for the given subject.
     * @param int $subjectID
     * @return void
     */
    private static function removeMethods(int $subjectID): void
    {
        $query = DB::query()->delete(DB::qn('#__organizer_subject_methods'))->where(DB::qc('subjectID', $subjectID));
        DB::set($query);
        DB::execute();
    }

    /**
     * Retrieves the ids of subjects registered as prerequisites for a given subject
     *
     * @param int    $subjectID the id of the subject
     * @param string $direction pre|post the direction of the subject dependency
     *
     * @return int[] the associated prerequisites
     */
    private static function requisites(int $subjectID, string $direction): array
    {
        if ($direction === 'pre') {
            $fromColumn = 'subjectID';
            $toColumn   = 'prerequisiteID';
        }
        else {
            $fromColumn = 'prerequisiteID';
            $toColumn   = 'subjectID';
        }

        $query = DB::query();
        $query->select('DISTINCT ' . DB::qn('target.subjectID'))
            ->from(DB::qn('#__organizer_curricula', 'target'))
            ->innerJoin(DB::qn('#__organizer_prerequisites', 'p'), DB::qc("p.$toColumn", 'target.id'))
            ->innerJoin(DB::qn('#__organizer_curricula', 'source'), DB::qc('source.id', "p.$fromColumn"))
            ->where(DB::qn('source.subjectID') . ' = :subjectID')->bind(':subjectID', $subjectID, ParameterType::INTEGER);
        DB::set($query);

        return DB::integers();
    }

    /**
     * Resolves identifiers to a table entry if possible.
     *
     * @param int    $HISinOneID the HISinOne 'ElementId' allows for targeted identification directly
     * @param string $code       the code allows for positive id of a module within the context of an organizational unit
     * @param int    $programID  the program's id in the curricula table
     * @return Table
     */
    private static function resolve(int $HISinOneID, string $code, int $programID): Table
    {
        $table = new Table();
        if ($table->load(['HISinOneID' => $HISinOneID])) {
            return $table;
        }

        $parent = Curricula::row($programID);

        $query = DB::query();
        $query->select(DB::qn('s.id'))
            ->from(DB::qn('#__organizer_subjects', 's'))
            ->innerJoin(DB::qn('#__organizer_curricula', 'c'), DB::qc('c.subjectID', 's.id'))
            ->where(DB::qcs([['s.code', $code, '=', true], ['c.lft', $parent['lft'], '>'], ['c.rgt', $parent['rgt'], '<']]));
        DB::set($query);

        if ($subjectID = DB::integer()) {
            $table->load($subjectID);
        }

        return $table;
    }

    /**
     * Attempts to resolve the text of 'Häufigkeit_des_Modulangebots' with one of the valid frequencies.
     * @param string $frequency
     * @return int
     */
    private static function resolveFrequency(string $frequency): int
    {
        $query = DB::query();
        $query->select(DB::qn('id'))
            ->from(DB::qn('#__organizer_frequencies'))
            ->where(DB::qc('name_de', "%$frequency%", 'LIKE', true));
        DB::set($query);
        return DB::integer(Frequencies::DEFAULT);
    }

    /**
     * Resolves the parsed methods to their ids in the database.
     * @param $methods
     * @return array
     */
    private static function resolveMethods($methods): array
    {
        $query = DB::query();
        $query->select(DB::qn('id'))
            ->from(DB::qn('#__organizer_methods'))
            ->where(DB::qc('name_de', ':method', 'LIKE'))
            ->bind(':method', $method);
        foreach ($methods as $index => $method) {
            DB::set($query);
            $methods[$index] = DB::integer();
        }

        return array_filter($methods);
    }

    /** @inheritDoc */
    public static function resources(): array
    {
        $poolID    = Input::integer('poolID', self::NONE);
        $programID = Input::integer('programID', self::NONE);
        $personID  = Input::integer('personID', self::NONE);

        // Without a valid restriction there are too many results.
        if ($poolID === self::NONE and $programID === self::NONE and $personID === self::NONE) {
            return [];
        }

        $query = DB::query();
        $tag   = Application::tag();

        $subjectID = DB::qn('s.id');
        $aliased   = [DB::qn("s.fullName_$tag", 'name')];
        $these     = ["DISTINCT $subjectID"];
        $those     = DB::qn(['p.surname, p.forename, p.title, p.username, s.code, s.creditPoints']);

        $query->select(array_merge($these, $those, $aliased))->from(DB::qn('#__organizer_subjects', 's'))
            ->order(DB::qn('name'))->group($subjectID);

        if ($ranges = self::filterRanges()) {
            $query->innerJoin(DB::qn('#__organizer_curricula', 'c'), DB::qc('c.subjectID', 's.id'));

            $count   = 1;
            $left    = DB::qn('c.lft');
            $right   = DB::qn('c.rgt');
            $wherray = [];

            foreach ($ranges as $range) {
                $bLeft     = ":left$count";
                $bRight    = ":right$count";
                $wherray[] = "( $left >= $bLeft AND $right <= $bRight )";
                $query->bind($bLeft, $range['lft'], ParameterType::INTEGER)->bind($bRight, $range['rgt'], ParameterType::INTEGER);
                $count++;
            }

            $query->where('(' . implode(' OR ', $wherray) . ')');
        }

        $condition = DB::qc('sp.subjectID', 's.id');
        $table     = DB::qn('#__organizer_subject_persons', 'sp');
        if ($personID) {
            $query->innerJoin($table, $condition)
                ->where("sp.personID = :personID")->bind(':personID', $personID, ParameterType::INTEGER);
        }
        else {
            $coordinates = Persons::COORDINATES;
            $query->leftJoin($table, $condition)
                ->where(DB::qc('sp.role', ':roleID'))->bind(':roleID', $coordinates, ParameterType::INTEGER);
        }

        $query->leftJoin(DB::qn('#__organizer_persons', 'p'), DB::qc('p.id', 'sp.personID'));

        DB::set($query);

        return DB::arrays('id');
    }

    /** @inheritDoc */
    public static function rows(array|int $identifiers): array
    {
        // Signature demands allowing an array to this point.
        if (!$identifiers or is_array($identifiers)) {
            return [];
        }

        $query = DB::query();
        $query->select('DISTINCT *')
            ->from(DB::qn('#__organizer_curricula'))
            ->where(DB::qn('subjectID') . ' = :subjectID')->bind(':subjectID', $identifiers, ParameterType::INTEGER)
            ->order(DB::qn('lft'));
        DB::set($query);

        return DB::arrays();
    }

    /**
     * Removes various inconsistencies that have appeared in the object values over the years from HTML/text values.
     * @param string $text
     * @return string
     */
    private static function sanitizeText(string $text): string
    {
        // Gets rid of bullshit encoding from copy and paste from word
        $text = str_replace(chr(160), ' ', $text);
        $text = str_replace(chr(173), '&shy', $text);
        $text = str_replace(chr(178), '&sup2', $text);
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

        // Run iterative whitespace and empty tag removal.
        do {
            $startText = $text;

            // Replace multiple whitespace characters with a single space
            $text = preg_replace('/\s+/', ' ', $text);

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
        } while ($text != $startText);

        return $text;
    }

    /**
     * Saves the dependencies to the prerequisites table
     *
     * @param array $programs     the programs that the schedule should be associated with
     * @param int   $subjectID    the id of the subject being processed
     * @param array $dependencies the subject dependencies
     *
     * @return bool
     */
    private static function saveDependencies(array $programs, int $subjectID, array $dependencies): bool
    {
        $subjectRanges = self::rows($subjectID);

        foreach ($programs as $program) {
            // Program context filtered subject ranges
            $subjectRanges = self::relevantRanges($program, $subjectRanges);
            $subjectIDs    = self::curriculumIDs($subjectRanges);

            // Program context filtered dependency ranges
            $prerequisiteIDs = [];
            foreach ($dependencies as $dependency) {
                $prRanges        = self::relevantRanges($program, $dependency);
                $prerequisiteIDs = array_merge($prerequisiteIDs, self::curriculumIDs($prRanges));
            }

            $prerequisiteIDs = array_unique($prerequisiteIDs);

            // Delete any and all old prerequisites in case there are now fewer.
            if ($subjectIDs) {
                $query = DB::query();
                $query->delete(DB::qn('#__organizer_prerequisites'))->whereIn(DB::qn('subjectID'), $subjectIDs);
                DB::set($query);
                DB::execute();
            }

            foreach ($prerequisiteIDs as $prerequisiteID) {
                foreach ($subjectIDs as $subjectID) {
                    $table = new Prerequisites();
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

    /** @inheritDoc */
    public static function subordinate(stdClass $resource, int $organizationID, int $parentID, int $programCID): bool
    {
        $code       = $resource->Modulcode;
        $HISinOneID = (int) $resource->ElementId ?? 0;
        $nameDE     = $resource->Titel->de ?? '';
        if (!$code or !$HISinOneID or !$nameDE) {
            Application::message('HIO_STRUCTURE_INVALID', Application::ERROR);
            return false;
        }

        $subject  = self::resolve($HISinOneID, $code, $programCID);
        $duration = (string) $resource->modulDetails->Moduldauer->de ?? '';

        // todo aliases
        $subject->duration    = (int) preg_match('/\d+/', $duration, $matches) ? $matches[0] : 1;
        $subject->code        = $code;
        $subject->fullName_de = $nameDE;
        $subject->fullName_en = $resource->Titel->en ?? $nameDE;
        $subject->expiration  = $resource->Gueltig_bis ?? date('Y-m-d', strtotime('+50 years'));
        $subject->HISinOneID  = $HISinOneID;
        $subject->language    = $resource->Sprache ?? 'de';

        foreach ($resource->modulDetails as $attribute => $localizations) {
            if (empty($localizations) or $attribute === 'Lehrende') {
                continue;
            }
            foreach ($localizations as $key => $localization) {
                $resource->modulDetails->$attribute->$key = self::sanitizeText((string) $localization);
            }
        }

        $subject->bonusPoints                 = self::bonusPoints($resource->modulDetails->Bonuspunkte->de ?? '');
        $subject->competences_de              = (string) $resource->modulDetails->Kompetenzen->de ?? '';
        $subject->competences_en              = (string) $resource->modulDetails->Kompetenzen->en ?? '';
        $subject->content_de                  = (string) $resource->modulDetails->Inhalte->de ?? '';
        $subject->content_en                  = (string) $resource->modulDetails->Inhalte->en ?? '';
        $subject->description_de              = (string) $resource->modulDetails->Kurzbescheibung->de ?? '';
        $subject->description_en              = (string) $resource->modulDetails->Kurzbescheibung->en ?? '';
        $subject->evaluated                   = ($value = (string) $resource->modulDetails->Benotung->de ?? '' and str_contains(strtolower($value), 'unbenotet')) ? 0 : 1;
        $subject->frequencyID                 = self::resolveFrequency($resource->modulDetails->Häufigkeit_des_Modulangebots->de);
        $subject->literature                  = (string) $resource->modulDetails->Literatur->de ?? '';
        $subject->method_de                   = (string) $resource->modulDetails->Lehrformen->de ?? '';
        $subject->method_en                   = (string) $resource->modulDetails->Lehrformen->en ?? '';
        $subject->preliminaryWork_de          = (string) $resource->modulDetails->Prüfungsvorleistungen->de ?? '';
        $subject->preliminaryWork_en          = (string) $resource->modulDetails->Prüfungsvorleistungen->en ?? '';
        $subject->proof_de                    = (string) $resource->modulDetails->Prüfungsleistungen->de ?? '';
        $subject->proof_en                    = (string) $resource->modulDetails->Prüfungsleistungen->en ?? '';
        $subject->recommendedPrerequisites_de = (string) $resource->modulDetails->Empfohlene_Voraussetzungen->de ?? '';
        $subject->recommendedPrerequisites_en = (string) $resource->modulDetails->Empfohlene_Voraussetzungen->en ?? '';
        self::calculated($resource, $subject);

        $coordinators = []; //$resource->modulDetails-><Something>->de => <Comma-Separated List of Teachers (Full Name with preceding Title)
        $teachers     = []; //$resource->modulDetails->Lehrende->de => <Comma-Separated List of Teachers (Full Name with preceding Title)
        //$subject->prerequisites_de = $resource->modulDetails-><Something>->de ?? '';
        //$subject->prerequisites_en = $resource->modulDetails-><Something>->en ?? '';

        if (!$subject->store()) {
            /** @noinspection PhpDeprecationInspection Error reporting is wildly inconsistent. This allows picking up Windows encoding errors until J6. */
            Application::message($subject->getError(), Application::ERROR);

            return false;
        }

        self::assignments($subject->id, $coordinators, $teachers);
        self::associate($organizationID, $subject->id);
        self::dependencies($subject);
        self::insert($parentID, $subject->id);
        self::methods($subject->id, $subject->method_de, $subject->sws);

        return true;
    }

    /**
     * Check if the user is one of the subject's teachers.
     *
     * @param int $subjectID the optional id of the subject
     * @param int $personID  the optional id of the person entry
     *
     * @return bool true if the user a teacher for the subject, otherwise false
     */
    public static function teaches(int $subjectID = 0, int $personID = 0): bool
    {
        if (!$personID = $personID ?: Persons::resolveUser(User::id())) {
            return false;
        }

        $teaches = Persons::TEACHES;
        $query   = DB::query();
        $query->select('COUNT(*)')
            ->from(DB::qn('#__organizer_subject_persons'))
            ->where(DB::qn('personID') . ' = :personID')->bind(':personID', $personID, ParameterType::INTEGER)
            ->where(DB::qn('role') . ' = :roleID')->bind(':roleID', $teaches, ParameterType::INTEGER);

        if ($subjectID) {
            $query->where(DB::qn('subjectID') . ' = :subjectID')->bind(':subjectID', $subjectID, ParameterType::INTEGER);
        }

        DB::set($query);

        return DB::bool();
    }

    /**
     * Removes person associations for the given subject.
     *
     * @param int $subjectID the subject id
     *
     * @return bool
     */
    public static function unassign(int $subjectID): bool
    {
        $query = DB::query()->delete(DB::qn('#__organizer_subject_persons'))->where(DB::qc('subjectID', $subjectID));
        DB::set($query);
        return DB::execute();
    }

    /**
     * Checks for subjects with the given possible module number associated with to the same programs.
     *
     * @param array $codes  the possible code values used in the attribute text
     * @param array $ranges the program ranges whose curricula contain the subject being processed
     *
     * @return array[]
     */
    private static function verifyDependencies(array $codes, array $ranges): array
    {
        $aliased  = DB::qn(['s.id', 'c.id'], ['subjectID', 'curriculumID']);
        $bound    = [':programID AS programID'];
        $selected = DB::qn(['abbreviation_de', 'abbreviation_en', 'code', 'fullName_de', 'fullName_en', 'c.lft', 'c.rgt']);

        $query = DB::query();
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

                DB::set($query);

                if (!$results = DB::arrays('curriculumID')) {
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
