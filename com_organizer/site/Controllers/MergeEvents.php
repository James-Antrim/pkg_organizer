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

use THM\Organizer\Adapters\{Application, Database as DB};
use THM\Organizer\Tables\SubjectEvents;

/** @inheritDoc */
class MergeEvents extends MergeController
{
    use Coordinated;

    protected string $list = 'Events';
    protected string $mergeContext = 'event';

    /** @inheritDoc */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        $data['active']      = $this->boolAggregate('active', 'events', false);
        $data['suppress']    = $this->boolAggregate('suppress', 'events', true);
        $data['preparatory'] = $this->boolAggregate('preparatory', 'events', true);

        $this->data = $data;

        return $data;
    }

    /**
     * Updates the instances table to reflect the merge of the events.
     * @return bool
     */
    private function updateInstances(): bool
    {
        $query = DB::query();
        $query->select(DB::qn(['blockID', 'unitID']))
            ->from(DB::qn('#__organizer_instances'))
            ->where(DB::qc('eventID', $this->mergeID));
        DB::set($query);

        // If there are existing delete any deprecated assignments to the same block and unit combination.
        if ($existing = DB::arrays()) {
            $deprecated = array_diff($this->mergeIDs, [$this->mergeID]);
            $query      = DB::query();
            $query->delete(DB::qn('#__organizer_instances'))
                ->where(DB::qc('blockID', ':blockID'))
                ->bind(':blockID', $blockID)
                ->where(DB::qc('unitID', ':unitID'))
                ->bind(':unitID', $unitID)
                ->whereIn(DB::qn('eventID'), $deprecated);
            foreach ($existing as $assignment) {
                $blockID = $assignment['blockID'];
                $unitID  = $assignment['unitID'];
                DB::set($query);
                DB::execute();
            }
        }

        // Any remaining assignments can be dealt with by a simple reference update.
        return $this->updateTable('instances');
    }

    /** @inheritDoc */
    protected function updateReferences(): bool
    {
        if (!$this->updateCoordinators('eventID', 'personID')) {
            Application::message('MERGE_FAILED_COORDINATORS', Application::ERROR);

            return false;
        }

        if (!$this->updateInstances()) {
            Application::message('MERGE_FAILED_ASSIGNMENTS', Application::ERROR);

            return false;
        }

        if (!$this->updateSubjectEvents()) {
            Application::message('MERGE_FAILED_SUBJECTS', Application::ERROR);

            return false;
        }

        return true;
    }

    /**
     * Updates the subject events table to reflect the merge of the events.
     * @return bool true on success, otherwise false;
     */
    private function updateSubjectEvents(): bool
    {
        if (!$subjectIDs = $this->getReferences('subject_events', 'subjectID')) {
            return true;
        }

        foreach ($subjectIDs as $subjectID) {
            $existing = null;

            foreach ($this->mergeIDs as $currentID) {
                $subjectEvent   = new SubjectEvents();
                $loadConditions = ['eventID' => $currentID, 'subjectID' => $subjectID];

                // The current subjectID is not associated with the current eventID
                if (!$subjectEvent->load($loadConditions)) {
                    continue;
                }

                // An existing association with the current eventID has already been found, remove potential duplicate.
                if ($existing) {
                    $subjectEvent->delete();
                    continue;
                }

                $subjectEvent->eventID = $this->mergeID;
                $existing              = $subjectEvent;
            }

            if ($existing and !$existing->store()) {
                return false;
            }
        }

        return true;
    }

    /** @inheritDoc */
    protected function validate(array &$data, array $required = []): void
    {
        parent::validate($data, ['code', 'name_de', 'name_en', 'organizationID']);
    }
}