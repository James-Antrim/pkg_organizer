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
use THM\Organizer\Tables\{EventCoordinators as Coordinator, InstancePersons as Assignment, SubjectPersons as Responsibility};

/** @inheritDoc */
class MergePersons extends MergeController
{
    protected string $list = 'Persons';
    protected string $mergeContext = 'person';

    /** @inheritDoc */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        $data['active']   = $this->boolAggregate('active', 'persons', false);
        $data['public']   = $this->boolAggregate('public', 'persons', true);
        $data['suppress'] = $this->boolAggregate('suppress', 'persons', true);

        return $data;
    }

    /**
     * Updates the instance persons table references to the mergeID.
     * @return bool
     */
    protected function updateAssignments(): bool
    {
        if (!$instanceIDs = $this->getReferences('instance_persons', 'instanceID')) {
            return true;
        }

        foreach ($instanceIDs as $instanceID) {
            $existing = null;

            foreach ($this->mergeIDs as $personID) {
                $keys    = ['instanceID' => $instanceID, 'personID' => $personID];
                $current = new Assignment();

                // The current personID is not associated with the current instance
                if (!$current->load($keys)) {
                    continue;
                }

                // Removed assignments need not be merged.
                if ($current->delta === 'removed') {
                    $current->delete();
                    continue;
                }

                if (!$existing) {
                    $existing = $current;
                    continue;
                }

                // Later assignments are more accurate in the same appointment context
                if ($current->modified < $existing->modified) {
                    $current->delete();
                    continue;
                }

                $existing->delete();
                $current->personID = $this->mergeID;
                $existing          = $current;
            }

            if ($existing and !$existing->store()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Updates the event coordinators table references to the mergeID.
     * @return bool
     */
    private function updateCoordinators(): bool
    {
        if (!$eventIDs = $this->getReferences('event_coordinators', 'eventID')) {
            return true;
        }

        foreach ($eventIDs as $eventID) {
            $existing = null;

            foreach ($this->mergeIDs as $currentID) {
                $coordinator = new Coordinator();
                $keys        = ['eventID' => $eventID, 'personID' => $currentID];

                // The current personID is not associated with the current eventID
                if (!$coordinator->load($keys)) {
                    continue;
                }

                // An existing association with the current eventID has already been found, making this a duplicate.
                if ($existing) {
                    $coordinator->delete();
                    continue;
                }

                $coordinator->personID = $this->mergeID;
                $existing              = $coordinator;
            }

            if ($existing and !$existing->store()) {
                return false;
            }
        }

        return true;
    }

    /** @inheritDoc */
    protected function updateReferences(): bool
    {
        if (!$this->updateAssignments()) {
            // Localize
            Application::message('MERGE_FAILED_ASSIGNMENTS', Application::ERROR);

            return false;
        }

        if (!$this->updateAssociations()) {
            // Localize
            Application::message('MERGE_FAILED_ASSOCIATIONS', Application::ERROR);
            return false;
        }

        if (!$this->updateCoordinators()) {
            // Localize
            Application::message('MERGE_FAILED_COORDINATORS', Application::ERROR);

            return false;
        }

        if (!$this->updateResponsibilities()) {
            // Localize
            Application::message('MERGE_FAILED_RESPONSIBILITIES', Application::ERROR);

            return false;
        }

        return true;
    }

    /**
     * Updates the subject persons table references to the mergeID.
     * @return bool
     */
    private function updateResponsibilities(): bool
    {
        $query = DB::getQuery();
        $query->select(['DISTINCT ' . DB::qn('subjectID'), DB::qn('role')])
            ->from(DB::qn('#__organizer_subject_persons'))
            ->whereIn(DB::qn('personID'), $this->mergeIDs);
        DB::setQuery($query);

        if (!$keyChain = DB::loadAssocList()) {
            return true;
        }

        foreach ($keyChain as $keys) {
            $existing = null;

            foreach ($this->mergeIDs as $personID) {
                $keys['personID'] = $personID;
                $responsibility   = new Responsibility();

                // The current personID is not associated with the current responsibility
                if (!$responsibility->load($keys)) {
                    continue;
                }

                // An existing association with the current responsibility has already been found, remove potential duplicate.
                if ($existing) {
                    $responsibility->delete();
                    continue;
                }

                $responsibility->personID = $this->mergeID;
                $existing                 = $responsibility;
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
        parent::validate($data, ['surname', 'code']);
    }
}