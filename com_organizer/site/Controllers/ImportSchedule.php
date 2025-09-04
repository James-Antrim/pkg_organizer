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

use SimpleXMLElement;
use stdClass;
use THM\Organizer\Adapters\{Application, Database as DB, Input, Text, User};
use THM\Organizer\Helpers\{Dates, Instances, Organizations as oHelper, Schedules as Helper};
use THM\Organizer\Tables\{Organizations as oTable, Schedules as Table, SubjectEvents};

/** @inheritDoc */
class ImportSchedule extends FormController
{
    use Scheduled;

    public stdClass $categories;
    public string $creationDate;
    public string $creationTime;
    public int $dateTime;
    public array $errors = [];
    public stdClass $events;
    public stdClass $grids;
    public stdClass $groups;
    public array $instances = [];
    protected string $list = 'Schedules';
    public int $organizationID;
    public stdClass $persons;
    public stdClass $rooms;
    public stdClass $schoolYear;
    public stdClass $term;
    public int $termID;
    // Prefix made necessary by a reflection method in the base controller. (teaching methods)
    public stdClass $tMethods;
    public stdClass $units;
    public array $warnings = [];

    /**
     * Removes booking and participation entries made irrelevant by scheduling changes.
     * @return void
     */
    private function cleanRegistrations(): void
    {
        $query = DB::query();
        $query->select('DISTINCT ' . DB::qn('i.id'))
            ->from(DB::qn('#__organizer_instances', 'i'))
            ->innerJoin(DB::qn('#__organizer_blocks', 'b'), DB::qc('b.id', 'i.blockID'))
            ->innerJoin(DB::qn('#__organizer_instance_participants', 'ip'), DB::qc('ip.instanceID', 'i.id'))
            ->where(DB::qc('i.delta', 'removed', '=', true));
        DB::set($query);

        if ($deprecated = DB::integers()) {
            $query = DB::query();
            $query->delete(DB::qn('#__organizer_instance_participants'))->whereIn(DB::qn('instanceID'), $deprecated);
            DB::set($query);
            DB::execute();
        }
    }

    /**
     * Creates a status report based upon object error and warning messages
     * @return void  outputs errors to the application
     */
    private function printStatusReport(): void
    {
        if ($this->errors) {
            $errorMessage = Text::_('ERROR_HEADER') . '<br />';
            $errorMessage .= implode('<br />', $this->errors);
            Application::message($errorMessage, Application::ERROR);
        }

        if ($this->warnings) {
            if (!$this->errors) {
                array_unshift($this->warnings, Text::_('WARNING_HEADER'));
            }
            Application::message(implode('<br />', $this->warnings), Application::WARNING);
        }
    }

    /**
     * Attempts to resolve events to subjects via associations and curriculum mapping.
     *
     * @param   int  $organizationID  the id of the organization with which the events are associated
     *
     * @return void
     */
    private function resolveEventSubjects(int $organizationID): void
    {
        $query = DB::query();
        $query->select(DB::qn(['id', 'subjectNo']))
            ->from(DB::qn('#__organizer_events'))
            ->where(DB::qcs([['organizationID', $organizationID], ['subjectNo', '', '!=', true]]));
        DB::set($query);

        if (!$events = DB::arrays()) {
            return;
        }

        foreach ($events as $event) {
            $query = DB::query();
            $query->select(['DISTINCT ' . DB::qn('lft'), DB::qn('rgt')])
                ->from(DB::qn('#__organizer_curricula', 'c'))
                ->innerJoin(DB::qn('#__organizer_programs', 'prg'), DB::qc('prg.id', 'c.programID'))
                ->innerJoin(DB::qn('#__organizer_categories', 'cat'), DB::qc('cat.id', 'prg.categoryID'))
                ->innerJoin(DB::qn('#__organizer_groups', 'gr'), DB::qc('gr.categoryID', 'cat.id'))
                ->innerJoin(DB::qn('#__organizer_instance_groups', 'ig'), DB::qc('ig.groupID', 'gr.id'))
                ->innerJoin(DB::qn('#__organizer_instance_persons', 'ip'), DB::qc('ip.id', 'ig.assocID'))
                ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.id', 'ip.instanceID'))
                ->where(DB::qc('i.eventID', $event['id']))
                ->order(DB::qn('lft') . ' DESC');
            DB::set($query);

            if (!$boundaries = DB::array()) {
                continue;
            }

            $subjectQuery = DB::query();
            $subjectQuery->select('subjectID')
                ->from(DB::qn('#__organizer_curricula', 'm'))
                ->innerJoin(DB::qn('#__organizer_subjects', 's'), DB::qc('m.subjectID', 's.id'))
                ->where(DB::qcs([
                    ['m.lft', $boundaries['lft'], '>'],
                    ['m.rgt', $boundaries['rgt'], '<'],
                    ['s.code', $event['subjectNo'], '=', true]
                ]));
            DB::set($subjectQuery);

            if (!$subjectID = DB::integer()) {
                continue;
            }

            $data         = ['subjectID' => $subjectID, 'eventID' => $event['id']];
            $subjectEvent = new SubjectEvents();

            if ($subjectEvent->load($data)) {
                continue;
            }

            $subjectEvent->save($data);
        }
    }

    /**
     * Validates and saves the data contained within the file to the database should validation be successful.
     * @return void
     */
    public function import(): void
    {
        $this->checkToken();
        $this->authorize();

        // Too big for joomla's comprehensive debugging.
        if (JDEBUG) {
            Application::message('ORGANIZER_DEBUG_ON', Application::ERROR);

            $this->setRedirect("$this->baseURL&view=schedules");
            return;
        }

        $file = Input::instance()->files->get('file');

        if (empty($file['type']) or $file['type'] !== 'text/xml') {
            Application::message('FILE_TYPE_INVALID', Application::ERROR);
            $this->setRedirect("$this->baseURL&view=importschedule");
            return;
        }

        if (mb_detect_encoding($file['tmp_name'], 'UTF-8', true) !== 'UTF-8') {
            Application::message('FILE_ENCODING_INVALID', Application::ERROR);
            $this->setRedirect("$this->baseURL&view=importschedule");
            return;
        }

        $xml = simplexml_load_file($file['tmp_name']);

        // Unused & mostly unfilled nodes
        unset($xml->lesson_date_schemes, $xml->lesson_tables, $xml->reductions);
        unset($xml->reduction_reasons, $xml->studentgroups, $xml->students);

        if (!$this->validateOrganization($xml->general)) {
            $this->setRedirect("$this->baseURL&view=importschedule");
            return;
        }

        $backDate = Input::string('date');

        // Creation Date & Time, school year dates, term attributes
        $this->creationDate = ($backDate and Dates::validate($backDate)) ? $backDate : trim((string) $xml[0]['date']);
        $validCreationDate  = $this->validateDate($this->creationDate, 'CREATION_DATE');
        $this->creationTime = trim((string) $xml[0]['time']);

        if ($valid = ($validCreationDate and $this->validateCreationTime())) {
            // Set the cut-off to the day before schedule generation to avoid inconsistencies on the creation date
            $this->dateTime = strtotime('-1 day', strtotime("$this->creationDate $this->creationTime"));
        }

        $this->modified = "$this->creationDate $this->creationTime";

        Validators\Terms::validate($this, $xml->general);

        // Errors at this stage are blocking.
        if ($this->errors) {
            $this->printStatusReport();
            $this->setRedirect("$this->baseURL&view=importschedule");
            return;
        }

        $valid = ($valid and !empty($this->term));
        unset($xml->general);

        // Check whether an identical schedule has already been uploaded
        $contextKeys = [
            'creationDate'   => $this->creationDate,
            'creationTime'   => $this->creationTime,
            'organizationID' => $this->organizationID,
            'termID'         => $this->termID
        ];

        $schedule = new Table();

        if ($schedule->load($contextKeys)) {
            Application::message('SCHEDULE_EXISTS', Application::ERROR);
            $this->setRedirect("$this->baseURL&view=importschedule");
            return;
        }

        $this->categories = new stdClass();
        foreach ($xml->departments->children() as $node) {
            Validators\Categories::validate($this, $node);
        }
        unset($xml->departments);

        $this->tMethods = new stdClass();
        foreach ($xml->descriptions->children() as $node) {
            Validators\Methods::validate($this, $node);
        }
        unset($xml->descriptions);

        $this->grids = new stdClass();
        foreach ($xml->timeperiods->children() as $node) {
            Validators\Grids::validate($this, $node);
        }
        Validators\Grids::setIDs($this);
        unset($xml->timeperiods);

        $this->events = new stdClass();
        foreach ($xml->subjects->children() as $node) {
            Validators\Events::validate($this, $node);
        }
        Validators\Events::setWarnings($this);
        unset($xml->subjects);

        $this->groups = new stdClass();
        foreach ($xml->classes->children() as $node) {
            Validators\Groups::validate($this, $node);
        }

        // Grids are not unset here because they are still used in lesson/instance processing.
        unset($this->categories, $xml->classes);

        $this->persons = new stdClass();
        foreach ($xml->teachers->children() as $node) {
            Validators\Persons::validate($this, $node);
        }
        Validators\Persons::setWarnings($this);
        unset($xml->teachers);

        $this->rooms = new stdClass();
        foreach ($xml->rooms->children() as $node) {
            Validators\Rooms::validate($this, $node);
        }
        Validators\Rooms::setWarnings($this);
        unset($xml->rooms);

        if ($valid) {
            $this->units = new stdClass();

            foreach ($xml->lessons->children() as $node) {
                Validators\Units::validate($this, $node);
            }

            Validators\Units::updateDates((array) $this->units);
            Validators\Units::setWarnings($this);
        }
        unset($this->events, $this->groups, $this->tMethods, $this->persons, $this->term, $xml);

        $this->printStatusReport();

        if ($this->errors) {
            $this->setRedirect("$this->baseURL&view=importschedule");
            return;
        }

        $data = [
            'creationDate'   => $this->creationDate,
            'creationTime'   => $this->creationTime,
            'organizationID' => $this->organizationID,
            'schedule'       => json_encode($this->instances),
            'termID'         => $this->termID,
            'userID'         => User::id()
        ];

        $schedule = new Table();
        if (!$schedule->save($data)) {
            Application::message('500', Application::ERROR);
            $this->setRedirect("$this->baseURL&view=importschedule");
            return;
        }

        $currentID = $schedule->id;

        // Sorted by date / time
        $contextIDs = Helper::contextIDs($this->organizationID, $this->termID);
        $current    = array_search($currentID, $contextIDs);

        $earlier = array_splice($contextIDs, 0, $current - 1);
        foreach (array_reverse($earlier) as $referenceID) {
            $refSchedule = new Table();
            $refSchedule->load($referenceID);

            if ($refSchedule->creationDate === $schedule->creationDate) {
                $refSchedule->delete();
                continue;
            }
            break;
        }

        $referenceID = empty($referenceID) ? 0 : $referenceID;

        if ($later = array_splice($contextIDs, $current + 1)) {
            $query = DB::query();
            $query->delete(DB::qn('#__organizer_schedules'))->whereIn(DB::qn('id'), $later);
            DB::set($query);
            DB::execute();
        }

        $this->update($currentID, $referenceID);

        //$bookings = new Bookings();
        //$bookings->clean();

        $this->cleanRegistrations();
        $this->resolveEventSubjects($this->organizationID);
        Instances::updatePublishing();

        $this->setRedirect("$this->baseURL&view=schedules");
    }

    /**
     * Validates a text attribute. Sets the attribute if valid.
     * @return bool true if the creation time is valid, otherwise false
     */
    private function validateCreationTime(): bool
    {
        if (empty($this->creationTime)) {
            $this->errors[] = Text::_("CREATION_TIME_MISSING");

            return false;
        }

        if (!preg_match('/^[\d]{6}$/', $this->creationTime)) {
            $this->errors[]     = Text::_("CREATION_TIME_INVALID");
            $this->creationTime = '';

            return false;
        }

        $this->creationTime = implode(':', str_split($this->creationTime, 2));

        return true;
    }

    /**
     * Validates a date attribute.
     *
     * @param   string &$value     the attribute value passed by reference because of reformatting to Y-m-d
     * @param   string  $constant  the unique text constant fragment
     *
     * @return bool
     */
    public function validateDate(string &$value, string $constant): bool
    {
        if (empty($value)) {
            $this->errors[] = Text::_("{$constant}_MISSING");

            return false;
        }

        if ($value = date('Y-m-d', strtotime($value))) {
            return true;
        }

        return false;
    }

    /**
     * Validates the organization context for the schedule and rights related to it.
     *
     * @param   SimpleXMLElement  $node
     *
     * @return bool
     */
    private function validateOrganization(SimpleXMLElement $node): bool
    {
        $orgCode = trim((string) $node->header1);

        if (empty($orgCode)) {
            Application::message('ORGANIZATION_MISSING', Application::ERROR);
            return false;
        }

        $organization = new oTable();
        if (!$organization->load(['abbreviation_de' => $orgCode])) {
            Application::message('ORGANIZATION_INVALID', Application::ERROR);
            return false;
        }

        if (!oHelper::schedulable($organization->id)) {
            Application::error(403);
        }

        if (!oHelper::allowsScheduling($organization->id)) {
            Application::error(501);
        }

        $this->organizationID = $organization->id;
        return true;
    }

    /**
     * Validates a text attribute. Sets the attribute if valid.
     *
     * @param   string  $value     the attribute value
     * @param   string  $constant  the unique text constant fragment
     * @param   string  $regex     the regex to check the text against
     *
     * @return bool false if blocking errors were found, otherwise true
     */
    public function validateText(string $value, string $constant, string $regex = ''): bool
    {
        if (empty($value)) {
            $this->errors[] = Text::_("{$constant}_MISSING");

            return false;
        }

        if (!empty($regex) and preg_match($regex, $value)) {
            $this->errors[] = Text::_("{$constant}_INVALID");

            return false;
        }

        return true;
    }
}