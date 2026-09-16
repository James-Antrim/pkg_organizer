<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2026 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use Joomla\CMS\Router\Route;
use THM\Organizer\Adapters\{Application, Database as DB, Input, Text, User};
use THM\Organizer\Helpers\{Dates, Instances as iHelper, Methods};
use THM\Organizer\Tables\{Blocks, InstanceParticipants as Table, Instances as iTable};

/**
 * Adds bookmarking functionality to controllers.
 */
trait Booked
{
    // Constants providing context for adding/removing instances to/from personal schedules though the interface.
    private const BLOCK = 2, SELECTED = 0, THIS = 1;

    /**
     * Finds instances matching the given instance by course and date.
     *
     * @param int[] &$instanceIDs the instance ids
     *
     * @return void
     */
    private function addCourseInstances(array &$instanceIDs): void
    {
        $after      = DB::qc('b2.startTime', 'b1.endTime', '>');
        $before     = DB::qc('b2.endTime', 'b1.startTime', '<');
        $exists     = DB::qn('u1.courseID ') . ' IS NOT NULL';
        $today      = date('Y-m-d');
        $futureDate = DB::qc('b1.date', $today, '>', true);
        $later      = DB::qc('b1.startTime', date('H:i:s'), '>', true);
        $then       = date('Y-m-d', strtotime('+2 days'));
        $today      = DB::qc('b1.date', $today, '=', true);

        $supplementalIDs = [];
        foreach ($instanceIDs as $instanceID) {
            $conditions = DB::qcs([
                ['i1.id', $instanceID, '!='],
                ['r.virtual', 0],
                ['i2.id', $instanceID],
                ['b1.date', 'b2.date'],
                ['b1.date', $then, '<=', true],
            ]);
            $query      = DB::query();
            $query->select('i1.id')
                ->from(DB::qn('#__organizer_instances', 'i1'))
                ->innerJoin(DB::qn('#__organizer_blocks', 'b1'), DB::qc('b1.id', 'i1.blockID'))
                ->innerJoin(DB::qn('#__organizer_instance_persons', 'ip'), DB::qc('ip.instanceID', 'i1.id'))
                ->innerJoin(DB::qn('#__organizer_instance_rooms', 'ir'), DB::qc('ir.assocID', 'ip.id'))
                ->innerJoin(DB::qn('#__organizer_rooms', 'r'), DB::qc('r.id', 'ir.roomID'))
                ->innerJoin(DB::qn('#__organizer_units', 'u1'), DB::qc('u1.id', 'i1.unitID'))
                ->innerJoin(DB::qn('#__organizer_units', 'u2'), DB::qc('u2.courseID', 'u1.courseID'))
                ->innerJoin(DB::qn('#__organizer_instances', 'i2'), DB::qc('i2.unitID', 'u2.id'))
                ->innerJoin(DB::qn('#__organizer_blocks', 'b2'), DB::qc('b2.id', 'i2.blockID'))
                ->where($conditions)
                ->where($exists)
                ->where("($before or $after)")
                ->where("($futureDate OR ($today and $later))");

            DB::set($query);
            $results = DB::integers();

            $supplementalIDs = array_merge($supplementalIDs, $results);
        }

        $instanceIDs = array_merge($instanceIDs, $supplementalIDs);
        $instanceIDs = array_unique($instanceIDs);
        $instanceIDs = array_filter($instanceIDs);
    }

    /**
     * Adds instances to the participant's personal schedule.
     *
     * @param int $method
     *
     * @return void
     */
    public function bookmark(int $method): void
    {
        $referrer = Input::instance()->server->getString('HTTP_REFERER');
        $this->setRedirect(Route::_($referrer, false));

        if (!$participantID = User::id()) {
            Application::message(Text::_('401'), Application::ERROR);
            return;
        }

        Participant::supplement($participantID);

        if (!$instanceIDs = $this->instanceIDs($method, true)) {
            return;
        }

        $bookmarked  = false;
        $responsible = false;

        foreach ($instanceIDs as $instanceID) {
            if (iHelper::hasResponsibility($instanceID)) {
                $responsible = true;
                continue;
            }

            $participation = new Table();
            $keys          = ['instanceID' => $instanceID, 'participantID' => $participantID];

            // Participant already has the appointment bookmarked.
            if ($participation->load($keys)) {
                continue;
            }

            if ($participation->save($keys)) {
                $bookmarked = true;
                $this->updateNumbers($instanceID);
            }
        }

        if ($bookmarked) {
            Application::message('SCHEDULE_SUCCESS');
        }
        elseif ($responsible) {
            Application::message('INSTANCE_RESPONSIBLE_NOTICE', Application::NOTICE);
        }
    }

    /**
     * Adds an instance to the participant's personal schedule.
     * @return void
     */
    public function bookmarkBlock(): void
    {
        $this->bookmark(self::BLOCK);
    }

    /**
     * Adds the selected instances to the participant's personal schedule.
     * @return void
     */
    public function bookmarkSelected(): void
    {
        $this->bookmark(self::SELECTED);
    }

    /**
     * Adds the current instance to the participant's personal schedule.
     * @return void
     */
    public function bookmarkThis(): void
    {
        $this->bookmark(self::THIS);
    }

    /**
     * Removes the participant's registrations.
     * @param int $method
     * @return void
     */
    public function deregister(int $method): void
    {
        $referrer = Input::instance()->server->getString('HTTP_REFERER');
        $this->setRedirect(Route::_($referrer, false));

        if (!$participantID = User::id()) {
            Application::message(Text::_('401'), Application::ERROR);

            return;
        }

        // This filters out past instances.
        if (!$instanceIDs = $this->instanceIDs($method)) {
            return;
        }

        $this->addCourseInstances($instanceIDs);

        $deregistered = false;

        foreach ($instanceIDs as $instanceID) {
            $participation = new Table();
            $keys          = ['instanceID' => $instanceID, 'participantID' => $participantID];

            // Participant was not registered to this instance.
            if (!$participation->load($keys) or !$participation->registered) {
                continue;
            }

            $keys['registered'] = false;

            if ($participation->save($keys)) {
                $deregistered = true;
                $this->updateNumbers($instanceID);
            }
        }

        if ($deregistered) {
            Application::message(Text::_('DEREGISTRATION_SUCCESS'));
        }
    }

    /**
     * Removes the participant's registration for the selected instances.
     * @return void
     */
    public function deregisterSelected(): void
    {
        $this->deregister(self::SELECTED);
    }

    /**
     * Removes the participant's registration for the current instance.
     * @return void
     */
    public function deregisterThis(): void
    {
        $this->deregister(self::THIS);
    }

    /**
     * Finds instances matching the given instance by matching method, inclusive the reference instance. Adds system message if no results were found.
     *
     * @param int  $method  the method for determining relevant instances
     * @param bool $virtual whether virtual instances are permissible in the result set
     *
     * @return int[]
     */
    private function instanceIDs(int $method, bool $virtual = false): array
    {
        $today = date('Y-m-d');

        $future = DB::qc('b.date', $today, '>', true);
        $later  = DB::qc('b.endTime', date('H:i:s'), '>', true);
        $today  = DB::QC('b.date', $today, '=', true);

        $query = DB::query();
        $query->select(DB::qn('i.id'))
            ->from(DB::qn('#__organizer_instances', 'i'))
            ->innerJoin(DB::qn('#__organizer_blocks', 'b'), DB::qc('b.id', 'i.blockID'))
            ->innerJoin(DB::qn('#__organizer_instance_persons', 'ip'), DB::qc('ip.instanceID', 'i.id'))
            ->innerJoin(DB::qn('#__organizer_instance_rooms', 'ir'), DB::qc('ir.assocID', 'ip.id'))
            ->innerJoin(DB::qn('#__organizer_rooms', 'r'), DB::qc('r.id', 'ir.roomID'))
            ->where("($future OR ($today AND $later))")
            ->order(DB::qn('i.id'));

        if (!$virtual) {
            $query->where(DB::qc('r.virtual', 0));
        }

        switch ($method) {
            case self::BLOCK:
                $block      = new Blocks();
                $instance   = new iTable();
                $instanceID = Input::id();
                if (!$instanceID or !$instance->load($instanceID) or !$block->load($instance->blockID)) {
                    return [];
                }

                $query->where(DB::qc('i.eventID', $instance->eventID))
                    ->where(DB::qc('i.unitID', $instance->unitID))
                    ->where(DB::qc('b.dow', $block->dow))
                    ->where(DB::qc('b.endTime', $block->endTime, '=', true))
                    ->where(DB::qc('b.startTime', $block->startTime, '=', true));
                DB::set($query);
                $instanceIDs = DB::integers();
                break;

            case self::THIS:
                $instance   = new iTable();
                $instanceID = Input::id();

                $instanceIDs = (!$instanceID or !$instance->load($instanceID)) ? [] : [$instanceID];
                break;

            case self::SELECTED:
            default:

                if (!$instanceIDs = Input::selectedIDs()) {
                    return [];
                }

                $selected = implode(',', $instanceIDs);
                $query->where("i.id IN ($selected)");
                DB::set($query);
                $instanceIDs = DB::integers();
                break;
        }

        if (!$instanceIDs = array_values($instanceIDs)) {
            Application::message(Text::_('NO_VALID_INSTANCES'), Application::NOTICE);
        }

        return $instanceIDs;
    }

    /**
     * Sends a circular mail to all course participants.
     * @return void
     */
    public function notify(): void
    {
        /*if (!$instanceID = Input::getID())
        {
            return false;
        }

        if (!Organizations::manageableIDs() and !Instances::teaches($instanceID))
        {
            Application::error(403);
        }

        $participants = Instances::getParticipantIDs($instanceID);
        $selected     = Input::getIntCollection('cid');

        if (empty($participants) and empty($selected))
        {
            return false;
        }

        $participantIDs = $selected ?: $participants;

        $form = Input::getBatchItems();
        if (!$subject = trim($form->get('subject', '')) or !$body = trim($form->get('body', '')))
        {
            return false;
        }

        foreach ($participantIDs as $participantID)
        {
            Helpers\Mailer::notifyParticipant($participantID, $subject, $body);
        }

        return true;*/
    }

    /**
     * Registers the participant for instances.
     * @param int $method
     * @return void
     */
    public function register(int $method): void
    {
        $referrer = Input::instance()->server->getString('HTTP_REFERER');
        $this->setRedirect(Route::_($referrer, false));

        if (!$participantID = User::id()) {
            Application::message(Text::_('401'), Application::ERROR);
            return;
        }

        Participant::supplement($participantID);

        // This filters out past instances.
        if (!$instanceIDs = $this->instanceIDs($method)) {
            return;
        }

        $this->addCourseInstances($instanceIDs);

        $registered  = false;
        $responsible = false;

        foreach ($instanceIDs as $instanceID) {
            if (iHelper::hasResponsibility($instanceID)) {
                $responsible = true;
                continue;
            }

            $participation = new Table();
            $keys          = ['instanceID' => $instanceID, 'participantID' => $participantID];

            // Participant is already registered.
            if ($participation->load($keys) and $participation->registered) {
                continue;
            }

            $name  = iHelper::name($instanceID);
            $block = iHelper::block($instanceID);
            $date  = Dates::formatDate($block->date);
            //$earliest  = Dates::formatDate(date('Y-m-d', strtotime('-2 days', strtotime($block->date))));
            $endTime   = Dates::formatEndTime($block->endTime);
            $startTime = Dates::formatTime($block->startTime);
            //$then      = date('Y-m-d', strtotime('+2 days'));

            if (iHelper::methodCode($instanceID) === Methods::FINALCODE) {
                Application::message(
                    Text::sprintf('INSTANCE_EXTERNAL_REGISTRATION', $name, $date, $startTime, $endTime),
                    Application::NOTICE
                );
                continue;
            }

            if (iHelper::getPresence($instanceID) === iHelper::ONLINE) {
                Application::message(
                    Text::sprintf('INSTANCE_ONLINE', $name, $date, $startTime, $endTime),
                    Application::NOTICE
                );
                continue;
            }

            /*if ($block->date > $then)
            {
                Application::message(
                    Text::sprintf('PREMATURE_REGISTRATION', $name, $date, $startTime, $endTime, $earliest),
                    Application::NOTICE
                );
                continue;
            }*/

            $query = DB::query();
            $query->select('i.id')
                ->from(DB::qn('#__organizer_instance_participants', 'ip'))
                ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.id', 'ip.instanceID'))
                ->where("i.id != $instanceID")
                ->where("i.blockID = $block->id")
                ->where('ip.registered = 1')
                ->where("ip.participantID = $participantID");
            DB::set($query);

            if ($otherInstanceID = DB::integer()) {
                $otherName = iHelper::name($otherInstanceID);
                Application::message(
                    Text::sprintf('INSTANCE_PREVIOUS_ENGAGEMENT', $date, $startTime, $endTime,
                        $otherName),
                    Application::NOTICE
                );
                continue;
            }

            if (iHelper::isFull($instanceID)) {
                Application::message(
                    Text::sprintf('INSTANCE_FULL_MESSAGE', $name, $date, $startTime, $endTime),
                    Application::NOTICE
                );
                continue;
            }

            $keys['registered'] = true;

            if ($participation->save($keys)) {
                $registered = true;
                $this->updateNumbers($instanceID);
            }
        }

        if ($registered) {
            Application::message(Text::_('REGISTRATION_SUCCESS'));
        }
        elseif ($responsible) {
            Application::message('INSTANCE_RESPONSIBLE_NOTICE', Application::NOTICE);
        }
    }

    /**
     * Registers the participant for the selected instances.
     * @return void
     */
    public function registerSelected(): void
    {
        $this->register(self::SELECTED);
    }

    /**
     * Registers the participant for the current instance.
     * @return void
     */
    public function registerThis(): void
    {
        $this->register(self::THIS);
    }

    /**
     * Removes instances from the participant's personal schedule.
     *
     * @param int $method
     *
     * @return void
     */
    private function removeBookmark(int $method): void
    {
        $referrer = Input::instance()->server->getString('HTTP_REFERER');
        $this->setRedirect(Route::_($referrer, false));

        if (!$participantID = User::id()) {
            Application::message(Text::_('401'), Application::ERROR);
            return;
        }

        if (!$instanceIDs = $this->instanceIDs($method, true)) {
            return;
        }

        $removed = false;

        foreach ($instanceIDs as $instanceID) {
            $participation = new Table();
            $keys          = ['instanceID' => $instanceID, 'participantID' => $participantID];

            // The instance was not in the participant's personal schedule.
            if (!$participation->load($keys)) {
                continue;
            }

            if ($participation->delete()) {
                $removed = true;
                $this->updateNumbers($instanceID);
            }
        }

        if ($removed) {
            Application::message(Text::_('DESCHEDULE_SUCCESS'));
        }
    }

    /**
     * Removes an instance from the participant's personal schedule.
     * personal schedule.
     * @return void
     */
    public function removeBookmarkBlock(): void
    {
        $this->removeBookmark(self::BLOCK);
    }

    /**
     * Removes the selected instances from the participant's personal schedule.
     * @return void
     */
    public function removeBookmarkSelected(): void
    {
        $this->removeBookmark(self::SELECTED);
    }

    /**
     * Removes the current instance from the participant's personal schedule.
     * @return void
     */
    public function removeBookmarkThis(): void
    {
        $this->removeBookmark(self::THIS);
    }

    /**
     * Updates participation numbers for a single instance.
     *
     * @param int $instanceID
     *
     * @return bool
     */
    public function updateNumbers(int $instanceID): bool
    {
        $query = DB::query();
        $query->select('*')->from(DB::qn('#__organizer_instance_participants'))->where("instanceID = $instanceID");
        DB::set($query);

        if (!$results = DB::arrays()) {
            return false;
        }

        $attended   = 0;
        $bookmarked = 0;
        $registered = 0;

        foreach ($results as $result) {
            $bookmarked++;
            $attended   = $attended + $result['attended'];
            $registered = $registered + $result['registered'];
        }

        $table = new iTable();
        $table->load($instanceID);

        $updated = false;

        if ($attended and $attended !== $table->attended) {
            $table->attended = $attended;
            $updated         = true;
        }

        if ($bookmarked and $bookmarked !== $table->bookmarked) {
            $table->bookmarked = $bookmarked;
            $updated           = true;
        }

        if ($registered and $registered !== $table->registered) {
            $table->registered = $registered;
            $updated           = true;
        }

        $table->store();

        return $updated;
    }
}