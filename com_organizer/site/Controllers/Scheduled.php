<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use Exception;
use THM\Organizer\Adapters\{Application, Database as DB, Input};
use THM\Organizer\Helpers\{Can, Roles, Schedulable};
use THM\Organizer\Tables\{Blocks, Schedules as Table, Table as BaseTable, Units};
use THM\Organizer\Tables\{InstanceGroups, InstancePersons, InstanceRooms, Instances, Modified};

/**
 * Standardizes maintenance of associations entries across resources.
 */
trait Scheduled
{
    /**
     * The Y-m-d H:i:s creation timestamp of the schedule being iterated.
     * @var string
     */
    public string $modified;

    /**
     * Gradually fills with a map of deprecated person ids to the ids that succeeded them, as required.
     * @var int[] oldPersonID => newPersonID
     */
    private array $personIDMap = [];

    /**
     * Creates/updates a 'new' instance person relation.
     *
     * @param   array  $instances   the instances of the schedule
     * @param   int    $instanceID  the id number of the instance being iterated
     * @param   int    $personID    the id number of the person being iterated
     *
     * @return void
     */
    private function addAssignment(array $instances, int $instanceID, int $personID): void
    {
        $assignment = new InstancePersons();
        $keys       = ['instanceID' => $instanceID, 'personID' => $personID];
        $roleID     = $instances[$instanceID][$personID]['roleID'];

        if ($assignment->load($keys)) {
            $this->updateAssociation($assignment, 'new', $roleID);
        }
        else {
            $this->addAssociation($assignment, $keys, 'new', $roleID);

            if (!$assignment->id) {
                if ($newID = $this->currentID($personID, $instanceID, array_keys($instances[$instanceID]))) {
                    $keys['personID'] = $newID;
                    $assignment->load($keys);
                }

                if (!$assignment->id) {
                    return;
                }
            }
        }

        foreach ($instances[$instanceID][$personID]['groups'] as $groupID) {
            $this->processResource('InstanceGroups', ['assocID' => $assignment->id, 'groupID' => $groupID], 'new');
        }

        foreach ($instances[$instanceID][$personID]['rooms'] as $roomID) {
            $this->processResource('InstanceRooms', ['assocID' => $assignment->id, 'roomID' => $roomID], 'new');
        }
    }

    /**
     * Updates an association table row with role, status and temporal changes.
     *
     * @param   BaseTable  $table   the table to update
     * @param   array      $fkMap   the fk column names mapped to values
     * @param   string     $delta   the status of the association
     * @param   int        $roleID  the role used for personell assignments
     *
     * @return void
     */
    private function addAssociation(BaseTable $table, array $fkMap, string $delta, int $roleID = Roles::TEACHER): void
    {
        foreach ($fkMap as $key => $value) {
            $table->$key = $value;
        }

        $table->delta    = $delta;
        $table->modified = $this->modified;

        if (property_exists($table, 'roleID')) {
            $table->roleID = $roleID;
        }

        try {
            $table->store();
        } // FK fails for resources merged out of existence
        catch (Exception $exception) {
            Application::message($exception->getMessage(), Application::ERROR);
            return;
        }
    }

    /**
     * Authorization check multiple curriculum resources. Individual resource authorization is later checked as appropriate.
     * @return void
     */
    protected function authorize(): void
    {
        if (Can::administrate()) {
            return;
        }

        $helperClass = empty($this->list) ? Application::uqClass(get_called_class()) : $this->list;

        /** @var Schedulable $helper */
        $helper = "THM\\Organizer\\Helpers\\" . $helperClass;
        $id     = Input::getID();

        if ($id ? !$helper::schedulable($id) : !$helper::schedulableIDs()) {
            Application::error(403);
        }
    }

    /**
     * Method to retrieve the valid person id after a deprecated one has been deleted through a merge.
     *
     * @param   int    $deprecatedID  the deprecated person id
     * @param   int    $instanceID    the id of the instance to which the person was assigned
     * @param   array  $personIDs     the person ids assigned to the instance in the JSON file
     *
     * @return int
     */
    private function currentID(int $deprecatedID, int $instanceID, array $personIDs): int
    {
        if (isset($this->personIDMap[$deprecatedID])) {
            return $this->personIDMap[$deprecatedID];
        }

        $personID = DB::qn('personID');
        $query    = DB::query();
        $query->select($personID)
            ->from(DB::qn('#__organizer_instance_persons'))
            ->whereNotIn($personID, $personIDs)
            ->where(DB::qc('instanceID', $instanceID));
        DB::set($query);

        if ($mergedID = DB::integer()) {
            $this->personIDMap[$deprecatedID] = $mergedID;
        }

        return $mergedID;
    }

    /**
     * Processes an individual group or room row.
     *
     * @param   string  $tableClass
     * @param   string  $delta
     * @param   array   $fkMap
     *
     * @return void
     */
    private function processResource(string $tableClass, array $fkMap, string $delta): void
    {
        $tableClass = "THM\\Organizer\\Tables\\" . $tableClass;

        /** @var InstanceGroups|InstanceRooms $table */
        $table = new $tableClass();

        $table->load($fkMap) ? $this->updateAssociation($table, $delta) : $this->addAssociation($table, $fkMap, $delta);
    }

    /**
     * Sets personell assignments and subordinate associations as removed.
     *
     * @param   array  $instances   the collection of instances modeling the reference schedule
     * @param   int    $instanceID  the id of the instance being currently iterated
     * @param   array  $personIDs   the person ids to set as removed
     *
     * @return void
     */
    private function removeAssignments(array $instances, int $instanceID, array $personIDs): void
    {
        foreach ($personIDs as $personID) {
            $assignment = new InstancePersons();

            // No need to create removed assignments that do not already exist
            if (!$assignment->load(['instanceID' => $instanceID, 'personID' => $personID])) {
                continue;
            }

            $assignment->delta    = 'removed';
            $assignment->modified = $this->modified;
            $assignment->store();

            foreach ($instances[$instanceID][$personID]['groups'] as $groupID) {
                $this->processResource('InstanceGroups', ['assocID' => $assignment->id, 'groupID' => $groupID], 'removed');
            }

            foreach ($instances[$instanceID][$personID]['rooms'] as $roomID) {
                $this->processResource('InstanceRooms', ['assocID' => $assignment->id, 'roomID' => $roomID], 'removed');
            }
        }
    }

    /**
     * Resets all associated resources to a removed status with a date of one week before the timestamp of the first
     * schedule in context.
     *
     * @param   int  $organizationID  the id of the organization context
     * @param   int  $termID          the id of the term context
     * @param   int  $baseID          the id if the schedule to be used to generate the reset timestamp
     *
     * @return void
     */
    private function resetContext(int $organizationID, int $termID, int $baseID): void
    {
        $firstSchedule = new Table();
        $firstSchedule->load($baseID);
        $timestamp = "$firstSchedule->creationDate $firstSchedule->creationTime";
        unset($firstSchedule);

        $modified   = date('Y-m-d h:i:s', strtotime('-2 Weeks', strtotime($timestamp)));
        $today      = date('Y-m-d');
        $conditions = [DB::qc('delta', 'removed', '=', true), DB::qc('modified', $modified, '=', true)];

        $query = DB::query();
        $query->select(DB::qn('id'))
            ->from(DB::qn('#__organizer_units'))
            ->where(DB::qcs([['organizationID', $organizationID], ['startDate', $today, '>=', true], ['termID', $termID]]))

            // Units created in organizer itself (future feature)
            ->where(DB::qc('code', '%-%', 'NOT LIKE', true));
        DB::set($query);

        if (!$unitIDs = DB::integers()) {
            return;
        }

        $query = DB::query();
        $query->update(DB::qn("#__organizer_units"))->set($conditions)->whereIn(DB::qn('id'), $unitIDs);
        DB::set($query);
        DB::execute();

        $startTime   = date('H:i:s');
        $tCondition1 = DB::qc('b.date', $today, '>', true);
        $tCondition2 = DB::qcs([['b.date', $today, '=', true], ['b.startTime', $startTime, '>', true]]);

        $query = DB::query();
        $query->select('DISTINCT ' . DB::qn('i.id'))
            ->from(DB::qn('#__organizer_instances', 'i'))
            ->innerJoin(DB::qn('#__organizer_blocks', 'b'), DB::qc('b.id', 'i.blockID'))
            ->innerJoin(DB::qn('#__organizer_units', 'u'), DB::qc('u.id', 'i.unitID'))
            ->where("($tCondition1 OR ($tCondition2))")
            ->where(DB::qcs([['u.organizationID', $organizationID], ['u.termID', $termID], ['u.startDate', $today, '>', true]]));
        DB::set($query);

        if (!$instanceIDs = DB::integers()) {
            return;
        }

        $query = DB::query();
        $query->update(DB::qn("#__organizer_instances"))->set($conditions)->whereIn(DB::qn('id'), $instanceIDs);
        DB::set($query);
        DB::execute();

        $query = DB::query();
        $query->select(DB::qn('id'))->from(DB::qn("#__organizer_instance_persons"))->whereIn(DB::qn('instanceID'), $instanceIDs);
        DB::set($query);

        if (!$assocIDs = DB::integers()) {
            return;
        }

        $query = DB::query();
        $query->update(DB::qn("#__organizer_instance_persons"))->set($conditions)->whereIn(DB::qn('id'), $assocIDs);
        DB::set($query);
        DB::execute();

        $query = DB::query();
        $query->select(DB::qn('id'))->from(DB::qn("#__organizer_instance_groups"))->whereIn(DB::qn('assocID'), $assocIDs);
        DB::set($query);

        if (!$igIDs = DB::integers()) {
            return;
        }

        $query = DB::query();
        $query->update(DB::qn("#__organizer_instance_groups"))->set($conditions)->whereIn(DB::qn('id'), $igIDs);
        DB::set($query);
        DB::execute();

        $query = DB::query();
        $query->select(DB::qn('id'))->from(DB::qn("#__organizer_instance_rooms"))->whereIn(DB::qn('assocID'), $assocIDs);
        DB::set($query);

        if (!$irIDs = DB::integers()) {
            return;
        }

        $query = DB::query();
        $query->update(DB::qn("#__organizer_instance_rooms"))->set($conditions)->whereIn(DB::qn('id'), $irIDs);
        DB::set($query);
        DB::execute();
    }

    /**
     * Determines whether the instance takes place after the schedule's creation.
     *
     * @param   Instances  $instance  the instance entry
     * @param   string     $date      the schedule's creation date
     * @param   string     $time      the schedule's creation time
     *
     * @return bool
     */
    private function subsequent(Instances $instance, string $date, string $time): bool
    {
        $block = new Blocks();

        if (!$block->load($instance->blockID)) {
            return false;
        }

        $future = $block->date > $date;
        $late   = ($block->date === $date and $block->startTime > $time);

        return ($future or $late);
    }

    /**
     * Sets the schedule with the given id as the current one in regard to the status of planned relationships and
     * resources in its organization / term context.
     *
     * @param   int  $scheduleID   the id of the schedule to set as current
     * @param   int  $referenceID  the id of the previously valid schedule
     *
     * @return void
     */
    private function update(int $scheduleID, int $referenceID): void
    {
        $current = new Table();

        if (!$current->load($scheduleID)) {
            return;
        }

        $curInstances = json_decode($current->schedule, true);
        $reference    = new Table();
        $refInstances = [];

        $this->modified = "$current->creationDate $current->creationTime";

        if ($referenceID) {
            if (!$reference->load($referenceID)) {
                return;
            }

            $refInstances = json_decode($reference->schedule, true);
        }

        $curInstanceIDs = array_keys($curInstances);
        $refInstanceIDs = array_keys($refInstances);

        // New instances
        $newUnitIDs = [];

        foreach (array_diff($curInstanceIDs, $refInstanceIDs) as $instanceID) {
            $instance = new Instances();

            if (!$instance->load($instanceID) or !$this->subsequent($instance, $current->creationDate, $current->creationTime)) {
                continue;
            }

            $this->updateAssociation($instance, 'new');
            $newUnitIDs[$instance->unitID] = $instance->unitID;

            foreach (array_keys($curInstances[$instanceID]) as $personID) {
                $this->addAssignment($curInstances, $instanceID, $personID);
            }
        }

        // Static instances
        $staticUnitIDs = [];
        foreach (array_intersect($curInstanceIDs, $refInstanceIDs) as $instanceID) {
            $instance = new Instances();

            if (!$instance->load($instanceID) or !$this->subsequent($instance, $current->creationDate, $current->creationTime)) {
                continue;
            }

            $this->updateAssociation($instance, '');
            $staticUnitIDs[$instance->unitID] = $instance->unitID;

            $curPersonIDs = array_keys($curInstances[$instanceID]);
            $refPersonIDs = array_keys($refInstances[$instanceID]);

            // New personell assignments
            foreach (array_diff($curPersonIDs, $refPersonIDs) as $personID) {
                $this->addAssignment($curInstances, $instanceID, $personID);
            }

            // Static persons
            $staticPersonIDs = array_intersect($curPersonIDs, $refPersonIDs);
            foreach ($staticPersonIDs as $personID) {
                $assignment = new InstancePersons();
                $keys       = ['instanceID' => $instanceID, 'personID' => $personID];
                $roleID     = $curInstances[$instanceID][$personID]['roleID'];

                if ($assignment->load($keys)) {
                    $this->updateAssociation($assignment, '', $roleID);
                }
                else {
                    $this->addAssociation($assignment, $keys, 'new', $roleID);

                    if (!$assignment->id) {
                        if ($newID = $this->currentID($personID, $instanceID, $staticPersonIDs)) {
                            $keys['personID'] = $newID;
                            $assignment->load($keys);
                        }

                        if (!$assignment->id) {
                            continue;
                        }
                    }
                }

                $curGroupIDs = $curInstances[$instanceID][$personID]['groups'];
                $refGroupIDs = $refInstances[$instanceID][$personID]['groups'];

                foreach (array_diff($curGroupIDs, $refGroupIDs) as $groupID) {
                    $this->processResource('InstanceGroups', ['assocID' => $assignment->id, 'groupID' => $groupID], 'new');
                }

                foreach (array_intersect($curGroupIDs, $refGroupIDs) as $groupID) {
                    $this->processResource('InstanceGroups', ['assocID' => $assignment->id, 'groupID' => $groupID], '');
                }

                foreach (array_diff($refGroupIDs, $curGroupIDs) as $groupID) {
                    $this->processResource('InstanceGroups', ['assocID' => $assignment->id, 'groupID' => $groupID], 'removed');
                }

                $curRoomIDs = $curInstances[$instanceID][$personID]['rooms'];
                $refRoomIDs = $refInstances[$instanceID][$personID]['rooms'];

                foreach (array_diff($curRoomIDs, $refRoomIDs) as $roomID) {
                    $this->processResource('InstanceRooms', ['assocID' => $assignment->id, 'roomID' => $roomID], 'new');
                }

                foreach (array_intersect($curRoomIDs, $refRoomIDs) as $roomID) {
                    $this->processResource('InstanceRooms', ['assocID' => $assignment->id, 'roomID' => $roomID], '');
                }

                foreach (array_diff($refRoomIDs, $curRoomIDs) as $roomID) {
                    $this->processResource('InstanceRooms', ['assocID' => $assignment->id, 'roomID' => $roomID], 'removed');
                }
            }

            // Removed persons
            $this->removeAssignments($refInstances, $instanceID, array_diff($refPersonIDs, $curPersonIDs));
        }

        // Removed instances
        $removedUnitIDs = [];

        foreach (array_diff($refInstanceIDs, $curInstanceIDs) as $instanceID) {
            $instance = new Instances();

            if (!$instance->load($instanceID) or !$this->subsequent($instance, $current->creationDate, $current->creationTime)) {
                continue;
            }

            $this->updateAssociation($instance, 'removed');
            $removedUnitIDs[$instance->unitID] = $instance->unitID;

            $this->removeAssignments($refInstances, $instanceID, array_keys($refInstances[$instanceID]));
        }

        // Update static unit definition based on use. Identity between keys and values ensure unique values.
        $staticUnitIDs = array_merge($staticUnitIDs, array_intersect($newUnitIDs, $removedUnitIDs));

        foreach (array_diff($newUnitIDs, $staticUnitIDs) as $unitID) {
            $unit = new Units();

            if ($unit->load($unitID)) {
                $this->updateAssociation($unit, 'new');
            }
        }

        foreach ($staticUnitIDs as $unitID) {
            $unit = new Units();

            if ($unit->load($unitID)) {
                $this->updateAssociation($unit, '');
            }
        }

        foreach (array_diff($removedUnitIDs, $staticUnitIDs) as $unitID) {
            $unit = new Units();

            if ($unit->load($unitID)) {
                $this->updateAssociation($unit, 'removed');
            }
        }
    }

    /**
     * Updates a table row with role, status and temporal changes.
     *
     * @param   BaseTable  $table   the table to update
     * @param   string     $delta   the status of the association
     * @param   int        $roleID  the role used for personell assignments
     *
     * @return void
     */
    private function updateAssociation(BaseTable $table, string $delta, int $roleID = Roles::TEACHER): void
    {
        /** @var Modified $table */
        $table->delta    = $delta;
        $table->modified = $this->modified;

        if (property_exists($table, 'roleID')) {
            /** @var InstancePersons $table */
            $table->roleID = $roleID;
        }

        $table->store();
    }
}
