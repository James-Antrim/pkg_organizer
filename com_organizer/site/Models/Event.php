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

use Joomla\Utilities\ArrayHelper;
use Organizer\Adapters\Database;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored event data.
 */
class Event extends MergeModel
{
    /**
     * @inheritDoc
     */
    protected function authorize()
    {
        if ($this->selected and !Helpers\Can::edit('events', $this->selected)) {
            Helpers\OrganizerHelper::error(403);
        } elseif ($eventID = Helpers\Input::getID() and !Helpers\Can::edit('events', $eventID)) {
            Helpers\OrganizerHelper::error(403);
        } elseif (!Helpers\Can::edit('events')) {
            Helpers\OrganizerHelper::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    public function getTable($name = '', $prefix = '', $options = [])
    {
        return new Tables\Events();
    }

    /**
     * Attempts to save the resource.
     *
     * @param array $data the data from the form
     *
     * @return int|bool int id of the resource on success, otherwise bool false
     */
    public function save(array $data = [])
    {
        if (!$eventID = parent::save($data)) {
            return false;
        }

        $data           = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;
        $coordinatorIDs = $data['coordinatorIDs'] ?? [];

        if ($coordinatorIDs) {
            $coordinatorIDs = ArrayHelper::toInteger($coordinatorIDs);
            foreach ($coordinatorIDs as $coordinatorID) {
                $coordinator = new Tables\EventCoordinators();
                $assocData   = ['eventID' => $eventID, 'personID' => $coordinatorID];
                if (!$coordinator->load($assocData)) {
                    $coordinator->save($assocData);
                }
            }
        }

        $query = Database::getQuery();
        $query->delete('#__organizer_event_coordinators')->where("eventID = $eventID");

        if ($coordinatorIDs) {
            $coordinatorIDs = implode(', ', $coordinatorIDs);
            $query->where("personID NOT IN ($coordinatorIDs)");
        }

        Database::setQuery($query);
        Database::execute();

        return $eventID;
    }

    /**
     * Updates the event coordinators table to reflect the merge of the events.
     * @return bool true on success, otherwise false;
     */
    private function updateEventCoordinators(): bool
    {
        if (!$personIDs = $this->getReferencedIDs('event_coordinators', 'personID')) {
            return true;
        }

        $mergeID = reset($this->selected);

        foreach ($personIDs as $personID) {
            $existing = null;

            foreach ($this->selected as $currentID) {
                $eventCoordinator = new Tables\EventCoordinators();
                $loadConditions   = ['eventID' => $currentID, 'personID' => $personID];

                // The current personID is not associated with the current eventID
                if (!$eventCoordinator->load($loadConditions)) {
                    continue;
                }

                // An existing association with the current eventID has already been found, remove potential duplicate.
                if ($existing) {
                    $eventCoordinator->delete();
                    continue;
                }

                $eventCoordinator->eventID = $mergeID;
                $existing                  = $eventCoordinator;
            }

            if ($existing and !$existing->store()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function updateReferences(): bool
    {
        if (!$this->updateEventCoordinators()) {
            return false;
        }

        if (!$this->updateSubjectEvents()) {
            return false;
        }

        //todo: rework the events -> instances consolidation to account for multiple merging events assigned to the same unit

        // This can fail in rare instances where the ~two events were assigned to the same unit.
        return $this->updateReferencingTable('instances');
    }

    /**
     * Updates the subject events table to reflect the merge of the events.
     * @return bool true on success, otherwise false;
     */
    private function updateSubjectEvents(): bool
    {
        if (!$subjectIDs = $this->getReferencedIDs('subject_events', 'subjectID')) {
            return true;
        }

        $mergeID = reset($this->selected);

        foreach ($subjectIDs as $subjectID) {
            $existing = null;

            foreach ($this->selected as $currentID) {
                $eventSubject   = new Tables\SubjectEvents();
                $loadConditions = ['eventID' => $currentID, 'subjectID' => $subjectID];

                // The current subjectID is not associated with the current eventID
                if (!$eventSubject->load($loadConditions)) {
                    continue;
                }

                // An existing association with the current eventID has already been found, remove potential duplicate.
                if ($existing) {
                    $eventSubject->delete();
                    continue;
                }

                $eventSubject->eventID = $mergeID;
                $existing              = $eventSubject;
            }

            if ($existing and !$existing->store()) {
                return false;
            }
        }

        return true;
    }
}
