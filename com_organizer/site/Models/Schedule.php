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

use THM\Organizer\Adapters\{Application, Database, Input, User};
use THM\Organizer\Controllers\Scheduled;
use THM\Organizer\Helpers\{Organizations, Schedules as Helper};
use THM\Organizer\Tables\Schedules as Table;
use THM\Organizer\Validators;

/**
 * Class which manages stored schedule data.
 * Note on access checks: since schedule access rights are set by organization, checking the access rights for one
 * schedule is sufficient for any other schedule modified in the same context.
 */
class Schedule extends FormModel
{
    use Scheduled;

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
     * Deletes the selected schedules.
     * @return bool true on successful deletion of all selected schedules, otherwise false
     */
    public function delete(): bool
    {
        if (!Organizations::schedulableIDs()) {
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
     * Deletes a single internal schedule entry and any corresponding external schedule entry that may exist.
     *
     * @param $scheduleID
     *
     * @return bool
     */
    private function deleteSingle($scheduleID): bool
    {
        if (!Organizations::schedulable($scheduleID)) {
            Application::error(403);
        }

        $schedule = new Table();

        if (!$schedule->load($scheduleID) or !$schedule->delete()) {
            return false;
        }

        return true;
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
            $subjectEvent = new SubjectEvents();

            if ($subjectEvent->load($data)) {
                continue;
            }

            $subjectEvent->save($data);
        }
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

        if (!Organizations::schedulable($organizationID)) {
            Application::error(403);
        }

        if (!Organizations::allowsScheduling($organizationID)) {
            Application::error(501);
        }

        $validator = new Validators\Schedule();

        if (!$validator->validate()) {
            return false;
        }

        $userID = User::id();
        unset($validator->schedule);

        $data = [
            'creationDate'   => $validator->creationDate,
            'creationTime'   => $validator->creationTime,
            'organizationID' => $organizationID,
            'schedule'       => json_encode($validator->instances),
            'termID'         => $validator->termID,
            'userID'         => $userID
        ];

        $schedule = new Table();
        if (!$schedule->save($data)) {
            return false;
        }

        $refScheduleIDs = Helper::contextIDs($organizationID, $validator->termID);

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

        $this->update($schedule->id, $referenceID);

        // With the deltas current it is now safe to remove any schedules of the same day as the schedule itself.
        foreach ($refScheduleIDs as $refScheduleID) {
            $refSchedule = new Table();
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