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

use Exception;
use Joomla\CMS\Table\Table;
use THM\Organizer\Adapters\{Application, Database, Input, Queries\QueryMySQLi};
use THM\Organizer\Helpers;
use THM\Organizer\Tables;
use THM\Organizer\Tables\{BaseTable, Schedules};
use THM\Organizer\Validators;

/**
 * Class which manages stored schedule data.
 * Note on access checks: since schedule access rights are set by organization, checking the access rights for one
 * schedule is sufficient for any other schedule modified in the same context.
 */
class Schedule extends BaseModel
{
    /**
     * The datetime string of the creation of the schedule file
     * @var string
     */
    private string $modified;

    /**
     * Gradually fills with a map of deprecated person ids to the ids that succeeded them, as required.
     * @var int[] oldPersonID => newPersonID
     */
    private array $personIDMap = [];

    /**
     * Cleans bookings according to their current status derived by the state of associated instances, optionally cleans
     * unattended past bookings.
     *
     * @param   bool  $cleanUnattended  whether unattended bookings in the past should be cleaned as well
     *
     * @return void
     */
    public function cleanBookings(bool $cleanUnattended = false): void
    {
        // Earlier bookings will have already been cleaned.
        $earliest = date('Y-m-d', strtotime('-14 days'));

        /* @var QueryMySQLi $query */
        $query = Database::getQuery();
        $query->selectX('DISTINCT bk.id', 'bookings AS bk', 'i.delta', ['removed'], true, true)
            ->innerJoinX('blocks AS bl', ['bl.id = bk.blockID'])
            ->innerJoinX('instances AS i', ['i.blockID = bk.blockID', 'i.unitID = bk.unitID'])
            ->where([Database::qn('bl.date') . " > '$earliest'"]);
        Database::setQuery($query);

        // Bookings associated with non-deprecated appointments.
        $currentIDs = Database::loadIntColumn();

        /** @var QueryMySQLi $query */
        $query = Database::getQuery();
        $query->selectX('DISTINCT bk.id', 'bookings AS bk', 'i.delta', ['removed'], false, true)
            ->innerJoinX('blocks AS bl', ['bl.id = bk.blockID'])
            ->innerJoinX('instances AS i', ['i.blockID = bk.blockID', 'i.unitID = bk.unitID'])
            ->where([Database::qn('bl.date') . " > '$earliest'"]);
        Database::setQuery($query);

        // Bookings associated with deprecated appointments.
        $unattendedIDs = Database::loadIntColumn();

        // Bookings solely with non-deprecated appointments.
        if ($deprecatedIDs = array_diff($unattendedIDs, $currentIDs)) {
            $this->deleteBookings($deprecatedIDs);
        }

        if (!$cleanUnattended) {
            return;
        }

        // Unattended past bookings. The inner join to instance participants allows archived bookings to not be selected here.

        $today = date('Y-m-d');
        /** @var QueryMySQLi $query */
        $query = Database::getQuery();
        $query->selectX('DISTINCT bk.id', 'bookings AS bk', 'ip.attended', [1])
            ->innerJoinX('blocks AS bl', ['bl.id = bk.blockID'])
            ->innerJoinX('instances AS i', ['i.blockID = bk.blockID', 'i.unitID = bk.unitID'])
            ->innerJoinX('instance_participants AS ip', ['ip.instanceID = i.id'])
            ->where([Database::qn('bl.date') . " < '$today'"]);
        Database::setQuery($query);

        // Attended bookings.
        $attendedIDs = Database::loadIntColumn();

        /** @var QueryMySQLi $query */
        $query = Database::getQuery();
        $query->selectX('DISTINCT bk.id', 'bookings AS bk', 'ip.attended', [0])
            ->innerJoinX('blocks AS bl', ['bl.id = bk.blockID'])
            ->innerJoinX('instances AS i', ['i.blockID = bk.blockID', 'i.unitID = bk.unitID'])
            ->innerJoinX('instance_participants AS ip', ['ip.instanceID = i.id'])
            ->where([Database::qn('bl.date') . " < '$today'"]);
        Database::setQuery($query);

        // Unattended bookings.
        $mixedIDs = Database::loadIntColumn();

        if ($unattendedIDs = array_diff($mixedIDs, $attendedIDs)) {
            $this->deleteBookings($unattendedIDs);
        }
    }

    /**
     * Removes booking and participation entries made irrelevant by scheduling changes.
     * @return void
     */
    private function cleanRegistrations(): void
    {
        $query = Database::getQuery();
        $query->select('DISTINCT i.id')
            ->from('#__organizer_instances AS i')
            ->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
            ->innerJoin('#__organizer_instance_participants AS ip ON ip.instanceID = i.id')
            ->where("i.delta = 'removed'");
        Database::setQuery($query);

        if ($deprecated = Database::loadColumn()) {
            $deprecated = implode(',', $deprecated);
            $query      = Database::getQuery();
            $query->delete('#__organizer_instance_participants')->where("instanceID IN ($deprecated)");
            Database::setQuery($query);
            Database::execute();
        }
    }

    /**
     * Updates a table associating an instance with a resource.
     *
     * @param   BaseTable  $table   the association table to be updated
     * @param   array      $keys    the keys used to identify the association through content
     * @param   string     $delta   the status of the association
     * @param   int        $roleID  the id of the role for instance person associations
     *
     * @return void
     */
    private function createAssoc(BaseTable $table, array $keys, string $delta, int $roleID = 1): void
    {
        foreach ($keys as $key => $value) {
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
     * Deletes the selected schedules.
     * @return bool true on successful deletion of all selected schedules, otherwise false
     */
    public function delete(): bool
    {
        if (!Helpers\Can::scheduleTheseOrganizations()) {
            Application::error(403);
        }

        $scheduleIDs = Input::getSelectedIDs();
        foreach ($scheduleIDs as $scheduleID) {
            if (!$this->deleteSingle($scheduleID)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deletes
     *
     * @param   array  $bookingIDs
     *
     * @return void
     */
    private function deleteBookings(array $bookingIDs): void
    {
        /* @var QueryMySQLi $query */
        $query = Database::getQuery();
        $query->deleteX('bookings', 'id', $bookingIDs);

        Database::setQuery($query);
        Database::execute();
    }

    /**
     * Removed duplicate entries (creationDate, creationTime, organizationID, termID) from the schedules table. No
     * authorization checks, because abuse would not result in actual data loss.
     * @return void
     */
    private function deleteDuplicates(): void
    {
        $conditions = [
            's1.creationDate = s2.creationDate',
            's1.creationTime = s2.creationTime',
            's1.organizationID = s2.organizationID',
            's1.termID = s2.termID'
        ];

        /* @var QueryMySQLi $query */
        $query = Database::getQuery();
        $query->select('s1.id')
            ->from('schedules AS s1')
            ->innerJoinX('schedules AS s2', $conditions)
            ->where('s1.id < s2.id');
        Database::setQuery($query);

        if (!$duplicateIDs = Database::loadIntColumn()) {
            return;
        }

        foreach ($duplicateIDs as $duplicateID) {
            $this->deleteSingle($duplicateID);
        }
    }

    /**
     * Deletes a single internal schedule entry and any corresponding external schedule entry that may exist.
     *
     * @param $scheduleID
     *
     * @return bool
     */
    private function deleteSingle($scheduleID): bool
    {
        if (!Helpers\Can::schedule('schedule', $scheduleID)) {
            Application::error(403);
        }

        $schedule = new Tables\Schedules();

        if (!$schedule->load($scheduleID) or !$schedule->delete()) {
            return false;
        }

        return true;
    }

    /**
     * Retrieves the ids of the resources associated with the given fk values.
     *
     * @param   string  $suffix    the specific portion of the table name
     * @param   string  $fkColumn  the name of the fk column
     * @param   array   $fkValues  the fk column values
     *
     * @return int[]
     */
    private function getAssociatedIDs(string $suffix, string $fkColumn, array $fkValues): array
    {
        $fkValues = implode(', ', $fkValues);
        $query    = Database::getQuery();
        /** @noinspection SqlResolve */
        $query->select('id')->from("#__organizer_$suffix")->where("$fkColumn IN ($fkValues)");
        Database::setQuery($query);

        return Database::loadIntColumn();
    }

    /**
     * Returns the schedule IDs relevant for the context ordered earliest to latest.
     *
     * @param   int  $organizationID  the id of the organization context
     * @param   int  $termID          the id of the term context
     *
     * @return int[] the schedule ids
     */
    private function getContextIDs(int $organizationID, int $termID): array
    {
        $query = Database::getQuery();
        $query->select('id')
            ->from('#__organizer_schedules')
            ->where("organizationID = $organizationID")
            ->where("termID = $termID")
            ->order('creationDate, creationTime');
        Database::setQuery($query);

        return Database::loadIntColumn();
    }

    /**
     * @inheritDoc
     */
    public function getTable($name = '', $prefix = '', $options = []): Schedules|Table
    {
        return new Tables\Schedules();
    }

    /**
     * Determines whether the instance is temporally relevant to the process.
     *
     * @param   Tables\Instances  $instance  the instance entry
     * @param   string            $date      the schedule's creation date
     * @param   string            $time      the schedule's creation time
     *
     * @return bool
     */
    private function isRelevant(Tables\Instances $instance, string $date, string $time): bool
    {
        $block = new Tables\Blocks();

        if (!$block->load($instance->blockID)) {
            return false;
        }

        $future = $block->date > $date;
        $late   = ($block->date === $date and $block->startTime > $time);

        return ($future or $late);
    }

    /**
     * Creates/updates a 'new' instance person relation.
     *
     * @param   array  $instances   the instances of the schedule
     * @param   int    $instanceID  the id number of the instance being iterated
     * @param   int    $personID    the id number of the person being iterated
     *
     * @return void
     */
    private function newPerson(array $instances, int $instanceID, int $personID): void
    {
        $iPerson = new Tables\InstancePersons();
        $keys    = ['instanceID' => $instanceID, 'personID' => $personID];
        $roleID  = $instances[$instanceID][$personID]['roleID'];

        if ($iPerson->load($keys)) {
            $this->updateAssoc($iPerson, 'new', $roleID);
        }
        else {
            $this->createAssoc($iPerson, $keys, 'new', $roleID);

            if (!$iPerson->id) {
                if ($newID = $this->getMergedID($personID, $instanceID, array_keys($instances[$instanceID]))) {
                    $keys['personID'] = $newID;
                    $iPerson->load($keys);
                }

                if (!$iPerson->id) {
                    return;
                }
            }
        }

        foreach ($instances[$instanceID][$personID]['groups'] as $ID) {
            $keys  = ['assocID' => $iPerson->id, 'groupID' => $ID];
            $table = new Tables\InstanceGroups();

            if ($table->load($keys)) {
                $this->updateAssoc($table, 'new');
            }
            else {
                $this->createAssoc($table, $keys, 'new');
            }
        }

        foreach ($instances[$instanceID][$personID]['rooms'] as $ID) {
            $keys  = ['assocID' => $iPerson->id, 'roomID' => $ID];
            $table = new Tables\InstanceRooms();

            if ($table->load($keys)) {
                $this->updateAssoc($table, 'new');
            }
            else {
                $this->createAssoc($table, $keys, 'new');
            }
        }
    }

    /**
     * Rebuilds the history of an organization / term context.
     * @return bool
     */
    public function rebuild(): bool
    {
        if (!$organizationID = Input::getFilterID('organization') or !$termID = Input::getFilterID('term')) {
            return false;
        }

        if (!Helpers\Can::schedule('organization', $organizationID)) {
            Application::error(403);
        }

        $this->deleteDuplicates();

        if (!$scheduleIDs = $this->getContextIDs($organizationID, $termID)) {
            return true;
        }

        $this->resetContext($organizationID, $termID, $scheduleIDs[0]);
        $referenceID = 0;

        foreach ($scheduleIDs as $scheduleID) {
            $this->setCurrent($scheduleID, $referenceID);
            $referenceID = $scheduleID;
        }

        return true;
    }

    /**
     * Rebuilds the history of an organization / term context.
     * @return bool
     */
    public function reference(): bool
    {
        if (!$referenceID = Input::getSelectedID()) {
            return false;
        }

        if (!Helpers\Can::schedule('schedule', $referenceID)) {
            Application::error(403);
        }

        $reference = new Tables\Schedules();
        if (!$reference->load($referenceID)) {
            return false;
        }

        if (!$scheduleIDs = $this->getContextIDs($reference->organizationID, $reference->termID)) {
            return true;
        }

        $currentID = array_pop($scheduleIDs);
        $current   = new Tables\Schedules();
        if (!$current->load($currentID)) {
            return false;
        }

        // The entries up to and including the reference id are ignored. The entries after are deleted.
        $delete = false;
        foreach ($scheduleIDs as $scheduleID) {
            if ($delete) {
                $this->deleteSingle($scheduleID);
            }

            if ($scheduleID == $referenceID) {
                $delete = true;
            }
        }

        $this->setCurrent($currentID, $referenceID);

        return true;
    }

    /**
     * Sets instance person associations and subordinate associations to removed.
     *
     * @param   array  $instances   the collection of instances modeling the reference schedule
     * @param   int    $instanceID  the id of the instance being currently iterated
     * @param   array  $personIDs   the collection/subset of person ids to set as removed
     *
     * @return void
     */
    private function removePersons(array $instances, int $instanceID, array $personIDs): void
    {
        foreach ($personIDs as $personID) {
            $iPerson = new Tables\InstancePersons();
            $ipKeys  = ['instanceID' => $instanceID, 'personID' => $personID];

            if (!$iPerson->load($ipKeys)) {
                continue;
            }

            $iPerson->delta    = 'removed';
            $iPerson->modified = $this->modified;
            $iPerson->store();

            foreach ($instances[$instanceID][$personID]['groups'] as $ID) {
                $this->removeResource('groups', $iPerson->id, $ID);
            }

            foreach ($instances[$instanceID][$personID]['rooms'] as $ID) {
                $this->removeResource('rooms', $iPerson->id, $ID);
            }
        }
    }

    /**
     * Sets the resource association indicated to removed.
     *
     * @param   string  $table       the instance resource table suffix
     * @param   int     $assocID     the id of the instance persons table entry
     * @param   int     $resourceID  the id of the associated resource
     *
     * @return void
     */
    private function removeResource(string $table, int $assocID, int $resourceID): void
    {
        switch ($table) {
            case 'groups':
                $keys  = ['assocID' => $assocID, 'groupID' => $resourceID];
                $table = new Tables\InstanceGroups();
                break;
            case 'rooms':
                $keys  = ['assocID' => $assocID, 'roomID' => $resourceID];
                $table = new Tables\InstanceRooms();
                break;
            default:
                return;
        }

        if ($table->load($keys)) {
            $this->updateAssoc($table, 'removed');
        }
        else {
            $this->createAssoc($table, $keys, 'removed');
        }
    }

    /**
     * Resets all associated resources to a removed status with a date of one week before the timestamp of the first
     * schedule.
     *
     * @param   int  $organizationID  the id of the organization context
     * @param   int  $termID          the id of the term context
     * @param   int  $baseID          the id if the schedule to be used to generate the reset timestamp
     *
     * @return void
     */
    private function resetContext(int $organizationID, int $termID, int $baseID): void
    {
        $firstSchedule = new Tables\Schedules();
        $firstSchedule->load($baseID);
        $timestamp = "$firstSchedule->creationDate $firstSchedule->creationTime";
        unset($firstSchedule);

        $modified   = date('Y-m-d h:i:s', strtotime('-2 Weeks', strtotime($timestamp)));
        $today      = date('Y-m-d');
        $conditions = ["delta = 'removed'", "modified = '$modified'"];

        $query = Database::getQuery();
        $query->select('id')
            ->from('#__organizer_units')
            ->where("code NOT LIKE '%-%'")
            ->where("organizationID = $organizationID")
            ->where("startDate > $today")
            ->where("termID = $termID");
        Database::setQuery($query);

        if (!$unitIDs = Database::loadIntColumn()) {
            return;
        }

        $this->updateBatch('units', $unitIDs, $conditions);
        $startTime = date('H:i:s');

        $query = Database::getQuery();
        $query->select('DISTINCT i.id')
            ->from('#__organizer_instances AS i')
            ->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
            ->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
            ->where("(b.date > '$today' OR (b.date = '$today' AND b.startTime > '$startTime'))")
            ->where("u.organizationID = $organizationID")
            ->where("u.startDate > $today")
            ->where("u.termID = $termID");
        Database::setQuery($query);

        if (!$instanceIDs = Database::loadIntColumn()) {
            return;
        }

        $this->updateBatch('instances', $instanceIDs, $conditions);

        if (!$assocIDs = $this->getAssociatedIDs('instance_persons', 'instanceID', $instanceIDs)) {
            return;
        }

        $this->updateBatch('instance_persons', $assocIDs, $conditions);

        if (!$igIDs = $this->getAssociatedIDs('instance_groups', 'assocID', $assocIDs)) {
            return;
        }

        $this->updateBatch('instance_groups', $igIDs, $conditions);

        if (!$irIDs = $this->getAssociatedIDs('instance_rooms', 'assocID', $assocIDs)) {
            return;
        }

        $this->updateBatch('instance_rooms', $irIDs, $conditions);
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
        $query = Database::getQuery();
        /** @noinspection SqlResolve */
        $query->select('id, subjectNo')
            ->from('#__organizer_events')
            ->where("organizationID = $organizationID")
            ->where("subjectNo != ''");
        Database::setQuery($query);

        if (!$events = Database::loadAssocList()) {
            return;
        }

        foreach ($events as $event) {
            $query = Database::getQuery();
            $query->select('DISTINCT lft, rgt')
                ->from('#__organizer_curricula AS c')
                ->innerJoin('#__organizer_programs AS prg ON prg.id = c.programID')
                ->innerJoin('#__organizer_categories AS cat ON cat.id = prg.categoryID')
                ->innerJoin('#__organizer_groups AS gr ON gr.categoryID = cat.id')
                ->innerJoin('#__organizer_instance_groups AS ig ON ig.groupID = gr.id')
                ->innerJoin('#__organizer_instance_persons AS ip ON ip.id = ig.assocID')
                ->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
                ->where("i.eventID = {$event['id']}")
                ->order('lft DESC');
            Database::setQuery($query);

            if (!$boundaries = Database::loadAssoc()) {
                continue;
            }

            $subjectQuery = Database::getQuery();
            $subjectQuery->select('subjectID')
                ->from('#__organizer_curricula AS m')
                ->innerJoin('#__organizer_subjects as s on m.subjectID = s.id')
                ->where("m.lft > '{$boundaries['lft']}'")
                ->where("m.rgt < '{$boundaries['rgt']}'")
                ->where("s.code = '{$event['subjectNo']}'");
            Database::setQuery($subjectQuery);

            if (!$subjectID = Database::loadInt()) {
                continue;
            }

            $data         = ['subjectID' => $subjectID, 'eventID' => $event['id']];
            $subjectEvent = new Tables\SubjectEvents();

            if ($subjectEvent->load($data)) {
                continue;
            }

            $subjectEvent->save($data);
        }
    }

    /**
     * Method to retrieve the valid person id after a deprecated one has been deleted through a merge.
     *
     * @param   int    $deprecatedID  the deprecated person id
     * @param   int    $instanceID    the id of the instance to which the person was assigned
     * @param   array  $personIDs     the person ids assigned to the instance in the JSON file
     *
     * @return int the id of the superseding person id
     */
    private function getMergedID(int $deprecatedID, int $instanceID, array $personIDs): int
    {
        if (!empty($this->personIDMap[$deprecatedID])) {
            return $this->personIDMap[$deprecatedID];
        }

        /** @var QueryMySQLi $query */
        $query = Database::getQuery();
        $query->selectX('personID', 'instance_persons', 'personID', $personIDs, true)->whereIn('instanceID', [$instanceID]);
        Database::setQuery($query);
        $mergedID = Database::loadInt();

        if ($mergedID) {
            $this->personIDMap[$deprecatedID] = $mergedID;
        }

        return $mergedID;
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
    public function setCurrent(int $scheduleID, int $referenceID): void
    {
        $schedule = new Tables\Schedules();

        if (!$schedule->load($scheduleID)) {
            return;
        }

        $instances  = json_decode($schedule->schedule, true);
        $reference  = new Tables\Schedules();
        $rInstances = [];

        $this->modified = "$schedule->creationDate $schedule->creationTime";

        if ($referenceID) {
            if (!$reference->load($referenceID)) {
                return;
            }

            $rInstances = json_decode($reference->schedule, true);
        }

        $instanceIDs  = array_keys($instances);
        $rInstanceIDs = array_keys($rInstances);

        // New instances ///////////////////////////////////////////////////////////////////////////////////////////////
        $NIUnitIDs = [];

        foreach (array_diff($instanceIDs, $rInstanceIDs) as $instanceID) {
            $instance = new Tables\Instances();

            if (!$instance->load($instanceID) or !$this->isRelevant($instance, $schedule->creationDate,
                    $schedule->creationTime)) {
                continue;
            }

            $this->updateAssoc($instance, 'new');
            $NIUnitIDs[$instance->unitID] = $instance->unitID;

            foreach (array_keys($instances[$instanceID]) as $personID) {
                $this->newPerson($instances, $instanceID, $personID);
            }
        }

        // Maintained instances ////////////////////////////////////////////////////////////////////////////////////////
        $SIUnitIDs = [];

        foreach (array_intersect($instanceIDs, $rInstanceIDs) as $instanceID) {
            $instance = new Tables\Instances();

            if (!$instance->load($instanceID)
                or !$this->isRelevant($instance, $schedule->creationDate, $schedule->creationTime)) {
                continue;
            }

            $this->updateAssoc($instance, '');
            $SIUnitIDs[$instance->unitID] = $instance->unitID;

            $personIDs  = array_keys($instances[$instanceID]);
            $rPersonIDs = array_keys($rInstances[$instanceID]);

            // New persons ///////////////////////////////////////////
            foreach (array_diff($personIDs, $rPersonIDs) as $personID) {
                $this->newPerson($instances, $instanceID, $personID);
            }

            // Maintained persons /////////////////////////////////////////
            $mPersonIDs = array_intersect($personIDs, $rPersonIDs);
            foreach ($mPersonIDs as $personID) {
                $iPerson = new Tables\InstancePersons();
                $keys    = ['instanceID' => $instanceID, 'personID' => $personID];
                $roleID  = $instances[$instanceID][$personID]['roleID'];

                if ($iPerson->load($keys)) {
                    $this->updateAssoc($iPerson, '', $roleID);
                }
                else {
                    $this->createAssoc($iPerson, $keys, 'new', $roleID);


                    if (!$iPerson->id) {
                        if ($newID = $this->getMergedID($personID, $instanceID, $mPersonIDs)) {
                            $keys['personID'] = $newID;
                            $iPerson->load($keys);
                        }

                        if (!$iPerson->id) {
                            return;
                        }
                    }
                }

                $IDs  = $instances[$instanceID][$personID]['groups'];
                $rIDs = $rInstances[$instanceID][$personID]['groups'];

                // New groups //////////////////////////
                foreach (array_diff($IDs, $rIDs) as $ID) {
                    $keys  = ['assocID' => $iPerson->id, 'groupID' => $ID];
                    $table = new Tables\InstanceGroups();

                    if ($table->load($keys)) {
                        $this->updateAssoc($table, 'new');
                    }
                    else {
                        $this->createAssoc($table, $keys, 'new');
                    }
                }

                // Maintained groups ////////////////////////
                foreach (array_intersect($IDs, $rIDs) as $ID) {
                    $keys  = ['assocID' => $iPerson->id, 'groupID' => $ID];
                    $table = new Tables\InstanceGroups();

                    if ($table->load($keys)) {
                        $this->updateAssoc($table, '');
                    }
                    else {
                        $this->createAssoc($table, $keys, '');
                    }
                }

                // Removed groups //////////////////////
                foreach (array_diff($rIDs, $IDs) as $ID) {
                    $this->removeResource('groups', $iPerson->id, $ID);
                }

                $IDs  = $instances[$instanceID][$personID]['rooms'];
                $rIDs = $rInstances[$instanceID][$personID]['rooms'];

                // New rooms ///////////////////////////
                foreach (array_diff($IDs, $rIDs) as $ID) {
                    $keys  = ['assocID' => $iPerson->id, 'roomID' => $ID];
                    $table = new Tables\InstanceRooms();

                    if ($table->load($keys)) {
                        $this->updateAssoc($table, 'new');
                    }
                    else {
                        $this->createAssoc($table, $keys, 'new');
                    }
                }

                // Maintained rooms /////////////////////////
                foreach (array_intersect($IDs, $rIDs) as $ID) {
                    $keys  = ['assocID' => $iPerson->id, 'roomID' => $ID];
                    $table = new Tables\InstanceRooms();

                    if ($table->load($keys)) {
                        $this->updateAssoc($table, '');
                    }
                    else {
                        $this->createAssoc($table, $keys, '');
                    }
                }

                // Removed rooms ///////////////////////
                foreach (array_diff($rIDs, $IDs) as $ID) {
                    $this->removeResource('rooms', $iPerson->id, $ID);
                }
            }

            // Removed persons //////////////////////////////
            $this->removePersons($rInstances, $instanceID, array_diff($rPersonIDs, $personIDs));
        }

        // Removed instances ///////////////////////////////////////////////////////////////////////////////////////////
        $ATLUnitIDs = [];

        foreach (array_diff($rInstanceIDs, $instanceIDs) as $instanceID) {
            $instance = new Tables\Instances();

            if (!$instance->load($instanceID)
                or !$this->isRelevant($instance, $schedule->creationDate, $schedule->creationTime)) {
                continue;
            }

            $this->updateAssoc($instance, 'removed');
            $ATLUnitIDs[$instance->unitID] = $instance->unitID;

            $this->removePersons($rInstances, $instanceID, array_keys($rInstances[$instanceID]));
        }

        /**
         * Unchanged Units are those in unchanged instances, past instances, or are shared by new instances and
         * removed future instances.
         */
        $unchangedIDs = array_merge($SIUnitIDs, array_intersect($NIUnitIDs, $ATLUnitIDs));
        foreach ($unchangedIDs as $ID) {
            $unit = new Tables\Units();

            if ($unit->load($ID)) {
                $this->updateAssoc($unit, '');
            }
        }

        foreach (array_diff($NIUnitIDs, $unchangedIDs) as $ID) {
            $unit = new Tables\Units();

            if ($unit->load($ID)) {
                $this->updateAssoc($unit, 'new');
            }
        }

        foreach (array_diff($ATLUnitIDs, $unchangedIDs) as $ID) {
            $unit = new Tables\Units();

            if ($unit->load($ID)) {
                $this->updateAssoc($unit, 'removed');
            }
        }
    }

    /**
     * Updates a table associating an instance with a resource.
     *
     * @param   BaseTable  $table   the association table to be updated
     * @param   string     $delta   the status of the association
     * @param   int        $roleID  the id of the role for instance person associations
     *
     * @return void
     */
    private function updateAssoc(BaseTable $table, string $delta, int $roleID = 1): void
    {
        $table->delta    = $delta;
        $table->modified = $this->modified;

        if (property_exists($table, 'roleID')) {
            $table->roleID = $roleID;
        }

        $table->store();
    }

    /**
     * Updates entries in the given entry ids in the given table with the given conditions.
     *
     * @param   string  $suffix      the specific portion of the table name
     * @param   array   $entryIDs    the ids of the entries to update
     * @param   array   $conditions  the set conditions
     *
     * @return void
     */
    private function updateBatch(string $suffix, array $entryIDs, array $conditions): void
    {
        $entryIDs = implode(', ', $entryIDs);
        $query    = Database::getQuery();
        $query->update("#__organizer_$suffix")->set($conditions)->where("id IN ($entryIDs)");
        Database::setQuery($query);
        Database::execute();
    }

    /**
     * Saves a schedule in the database for later use
     * @return  bool true on success, otherwise false
     */
    public function upload(): bool
    {
        if (!$organizationID = Input::getInt('organizationID')) {
            return false;
        }

        if (!Helpers\Can::schedule('organization', $organizationID)) {
            Application::error(403);
        }

        if (!Helpers\Organizations::allowScheduling($organizationID)) {
            Application::error(501);
        }

        $validator = new Validators\Schedule();

        if (!$validator->validate()) {
            return false;
        }

        $userID = Helpers\Users::getID();
        unset($validator->schedule);

        $data = [
            'creationDate'   => $validator->creationDate,
            'creationTime'   => $validator->creationTime,
            'organizationID' => $organizationID,
            'schedule'       => json_encode($validator->instances),
            'termID'         => $validator->termID,
            'userID'         => $userID
        ];

        $schedule = new Tables\Schedules();
        if (!$schedule->save($data)) {
            return false;
        }

        $refScheduleIDs = $this->getContextIDs($organizationID, $validator->termID);

        // Remove current from iteration.
        array_pop($refScheduleIDs);

        // Get the last element without removing it from iteration.
        $referenceID = end($refScheduleIDs);

        // Ensures a clean reset if there were previous schedules that have been removed.
        if (!$referenceID) {
            $this->resetContext($organizationID, $validator->termID, $schedule->id);

            // end() of empty is false this future proofs the typing
            $referenceID = 0;
        }

        $this->setCurrent($schedule->id, $referenceID);

        // With the deltas current it is now safe to remove any schedules of the same day as the schedule itself.
        foreach ($refScheduleIDs as $refScheduleID) {
            $refSchedule = new Tables\Schedules();
            $refSchedule->load($refScheduleID);

            if ($refSchedule->creationDate === $schedule->creationDate) {
                $refSchedule->delete();
            }
        }

        $this->cleanBookings();
        $this->cleanRegistrations();
        $this->resolveEventSubjects($organizationID);

        return true;
    }
}