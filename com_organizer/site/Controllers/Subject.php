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

use THM\Organizer\Adapters\{Application, Database as DB, Input, Text};
use THM\Organizer\Helpers\{HISinOne, Persons as PHelper, Programs, Subjects as Helper};
use THM\Organizer\Tables\{Prerequisites, SubjectMethods, SubjectPersons, Subjects as Table};

/** @inheritDoc */
class Subject extends CurriculumResource
{
    private const PRE = 1;

    protected const NONE = -1;

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
     * Processes assignments from the form.
     *
     * @return bool
     */
    private function assignments(): bool
    {
        $data = $this->data;

        // More efficient to remove all subject persons associations for the subject than iterate the persons table
        if (!Helper::unassign($data['id'])) {
            return false;
        }

        $coordinators = empty($data['coordinators']) ? [] : $data['coordinators'];
        $teachers     = empty($data['persons']) ? [] : $data['persons'];

        if (!$coordinators and !$teachers) {
            return true;
        }

        if ($coordinators and $persons = array_filter($coordinators)) {
            foreach ($persons as $personID) {
                $spData = ['personID' => $personID, 'role' => PHelper::COORDINATES, 'subjectID' => $data['id']];
                $table  = new SubjectPersons();

                if (!$table->save($spData)) {
                    return false;
                }
            }

        }

        if ($teachers and $persons = array_filter($teachers)) {
            foreach ($persons as $personID) {
                $spData = ['personID' => $personID, 'role' => PHelper::TEACHES, 'subjectID' => $data['id']];
                $table  = new SubjectPersons();

                if (!$table->save($spData)) {
                    return false;
                }
            }
        }

        return true;
    }

    /** @inheritDoc */
    protected function import(int $resourceID = 0): void
    {
        if (!$resourceID) {
            return;
        }

        $client = new HISinOne();

        if (!$HISinOneID = Helper::HISinOneID($resourceID)) {
            Application::message('HIO_DATA_MISSING', Application::WARNING);
            return;
        }

        if (!$response = $client->subject($HISinOneID)) {
            Application::message(Text::sprintf('HIO_SUBJECT_DATA_INCONSISTENT', $HISinOneID, $resourceID), Application::WARNING);
            return;
        }

        if (!$response = $response->module_out ?? false or !Helper::validateResponse($response)) {
            Application::message('HIO_STRUCTURE_INVALID', Application::ERROR);
            return;
        }

        $subject = new Table();
        $subject->load($resourceID);

        Helper::importSingle($subject, $response);
    }

    /** @inheritDoc */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        // External references are not in the table and as such won't be automatically prepared.
        $data['coordinators']    = Input::resourceIDs('coordinators');
        $data['organizationIDs'] = Input::resourceIDs('organizationIDs');
        $data['persons']         = Input::resourceIDs('persons');
        $data['prerequisites']   = Input::resourceIDs('prerequisites');
        $data['programIDs']      = Input::resourceIDs('programIDs');
        $data['superordinates']  = Input::resourceIDs('superordinates');

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

        if (!$id = Input::id()) {
            http_response_code(400);
            echo '';
            $this->app->close();
        }

        $options = '';
        $ranges  = Programs::programs(Input::resourceIDs('programIDs'));

        foreach (Helper::preOptions($id, $ranges) as $option) {
            $options .= "<option value='$option->value' $option->selected $option->disable>$option->text</option>";
        }

        echo $options;

        $this->app->close();
    }

    /** @inheritDoc */
    public function postProcess(): void
    {
        if (!$this->assignments()) {
            Application::message('UPDATE_ASSIGNMENT_FAILED', Application::WARNING);
        }

        Helper::updateSuperOrdinates($this->data);

        /*if (!$this->processEvents())
        {
            Application::message('TBD', Application::WARNING);
        }*/

        $this->processMethods();

        // Dependant on curricula entries.
        if (!$this->processPrerequisites()) {
            Application::message('UPDATE_DEPENDENCY_FAILED', Application::WARNING);
        }
    }

    /**
     * Processes the subject's method distribution by weekly school hours.
     * @return void
     */
    private function processMethods(): void
    {
        $subjectID = $this->data['id'];
        $sum       = 0;
        $sws       = $this->data['sws'];

        Helper::removeMethods($subjectID);
        foreach (Input::array('methods') as $method) {
            $method['subjectID'] = $subjectID;
            $subjectMethod       = new SubjectMethods();
            $subjectMethod->save($method);
            $sum += $method['sws'];
        }

        if ($sum !== $sws) {
            Application::message(Text::sprintf('SUBJECT_METHODS_INCONSISTENT', $sum, $sws), Application::WARNING);
        }
    }

    /**
     * Processes the subject prerequisites selected for the subject.
     *
     * @return bool
     */
    private function processPrerequisites(): bool
    {
        $subjectID = $this->data['id'];

        // Unmapped => impossible to create a dependency hierarchy
        if (!$subjectRanges = Helper::rows($subjectID)) {
            return true;
        }

        $programRanges = Programs::rows($subjectRanges);

        if ($prerequisites = array_filter($this->data['prerequisites']) and !in_array(self::NONE, $prerequisites)) {
            $prerequisiteRanges = [];
            foreach ($prerequisites as $prerequisiteID) {
                $prerequisiteRanges = array_merge($prerequisiteRanges, Helper::rows($prerequisiteID));
            }

            foreach ($programRanges as $programRange) {

                // 'r' is for relevant
                if (!$rprRanges = Helper::relevantRanges($programRange, $prerequisiteRanges)) {
                    continue;
                }

                if (!$rsRanges = Helper::relevantRanges($programRange, $subjectRanges)) {
                    continue;
                }

                // Remove deprecated associations
                $query = DB::query();
                $query->delete('#__organizer_prerequisites')
                    ->whereIn(DB::qn('subjectID'), Helper::curriculumIDs($rsRanges))
                    ->whereNotIn(DB::qn('prerequisiteID'), Helper::curriculumIDs($rprRanges));
                DB::set($query);

                if (!DB::execute()) {
                    return false;
                }

                foreach ($rprRanges as $rprRange) {
                    foreach ($rsRanges as $rsRange) {
                        $data = ['subjectID' => $rsRange['id'], 'prerequisiteID' => $rprRange['id']];

                        $prerequisites = new Prerequisites();
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
            $success = Helper::removeDependencies($subjectID, self::PRE);
        }

        return $success;
    }
}