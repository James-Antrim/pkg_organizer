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

use THM\Organizer\Adapters\{Application, Database as DB, Input, User};
use THM\Organizer\Tables\{CourseParticipants as CParticipation, InstanceParticipants as IParticipation, Users};
use Joomla\Database\ParameterType;

/** @inheritDoc */
class MergeParticipants extends MergeController
{
    private string $email;
    protected string $list = 'Participants';
    protected string $mergeContext = 'participant';

    /**
     * Updates the course participants table to only reference the mergeID.
     * @return bool
     */
    private function courses(): bool
    {
        if (!$courseIDs = $this->getReferences('course_participants', 'courseID')) {
            return true;
        }

        foreach ($courseIDs as $courseID) {
            $attended   = false;
            $existing   = null;
            $paid       = false;
            $registered = '';

            foreach ($this->mergeIDs as $participantID) {
                $assoc   = ['courseID' => $courseID, 'participantID' => $participantID];
                $current = new CParticipation();

                // The current participantID is not associated with the current course
                if (!$current->load($assoc)) {
                    continue;
                }

                $attended   = (int) ($attended or $current->attended);
                $paid       = (int) ($paid or $current->paid);
                $registered = ($registered and $registered < $current->participantDate) ? $registered : $current->participantDate;

                // A previously iterated entry has already been set aside for aggregation.
                if ($existing) {
                    $current->delete();
                    continue;
                }

                $existing = $current;
            }

            // Should not be able to occur, but covering bases.
            if (!$existing) {
                continue;
            }

            $existing->attended        = $attended;
            $existing->paid            = $paid;
            $existing->participantDate = $registered;
            $existing->participantID   = $this->mergeID;

            if (!$existing->store()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Maps the user id to the relevant group ids.
     *
     * @param   int[]  $groupIDs  the group ids
     *
     * @return void
     */
    private function groups(array $groupIDs): void
    {
        $map      = DB::qn('#__user_usergroup_map');
        $mGroupID = DB::qn('group_id');
        $mUserID  = DB::qn('user_id');

        $insert = DB::query()->insert($map)->columns([$mGroupID, $mUserID])->values(":groupID, :userID");
        $select = DB::query()->select('*')->from($map)->where("$mUserID = :userID")->where("$mGroupID = :groupID");

        foreach ($groupIDs as $groupID) {
            $select->bind(':groupID', $groupID, ParameterType::INTEGER)->bind(':userID', $this->mergeID, ParameterType::INTEGER);
            DB::set($select);

            if (DB::array()) {
                continue;
            }

            $insert->bind(':groupID', $groupID, ParameterType::INTEGER)->bind(':userID', $this->mergeID, ParameterType::INTEGER);
            DB::set($insert);
            DB::execute();
        }

        $delete = DB::query();
        $delete->delete($map)
            ->where("$mUserID = :userID")
            ->whereNotIn($mGroupID, $groupIDs)
            ->bind(':userID', $this->mergeID, ParameterType::INTEGER);
        DB::set($delete);
        DB::execute();
    }

    /**
     * Updates the instance participants table to only reference the mergeID.
     * @return bool
     */
    private function instances(): bool
    {
        if (!$instanceIDs = $this->getReferences('instance_participants', 'instanceID')) {
            return true;
        }

        foreach ($instanceIDs as $instanceID) {
            $attended   = false;
            $existing   = null;
            $registered = false;
            $roomID     = null;
            $seat       = null;

            foreach ($this->mergeIDs as $participantID) {
                $assoc   = ['instanceID' => $instanceID, 'participantID' => $participantID];
                $current = new IParticipation();

                // The current participantID is not associated with the current instance
                if (!$current->load($assoc)) {
                    continue;
                }

                $attended   = (int) ($attended or $current->attended);
                $registered = (int) ($registered or $current->registered);
                $roomID     = $current->roomID ?? $roomID;
                $seat       = $current->seat ?? $seat;

                // A previously iterated entry has already been set aside for aggregation.
                if ($existing) {
                    $current->delete();
                    continue;
                }

                $existing = $current;
            }

            // Should not be able to occur, but covering bases.
            if (!$existing) {
                continue;
            }

            $existing->attended      = $attended;
            $existing->participantID = $this->mergeID;
            $existing->registered    = $registered;
            $existing->roomID        = $roomID;
            $existing->seat          = $seat;

            if (!$existing->store()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolves the incoming ids for later ease of use.
     * @return void
     */
    protected function resolveIDs(): void
    {
        $mergeIDs = Input::resourceIDs('ids');
        asort($mergeIDs);

        $query = DB::query();
        $query->select(DB::qn(['id', 'email']))
            ->from(DB::qn('#__users'))
            ->whereIn(DB::qn('id'), $mergeIDs)
            ->order(DB::qn('id'));
        DB::set($query);

        if (!$results = DB::arrays('id') or count($results) !== count($mergeIDs)) {
            Application::error(500);
        }

        $this->mergeIDs = $mergeIDs;

        foreach ($mergeIDs as $index => $mergeID) {
            if ($results[$mergeID]['email'] === $this->email) {
                $this->mergeID = $mergeID;
                unset($mergeIDs[$index]);
                break;
            }
        }

        // No user's email matched the selected
        if (!$this->mergeID) {
            Application::error(500);
        }

        $this->deprecatedIDs = $mergeIDs;
    }

    /** @inheritDoc */
    protected function updateReferences(): bool
    {
        if (!$this->courses()) {
            Application::message('MERGE_FAILED_COURSE_PARTICIPATION', Application::ERROR);

            return false;
        }

        if (!$this->instances()) {
            Application::message('MERGE_FAILED_INSTANCE_PARTICIPATION', Application::ERROR);

            return false;
        }

        if (!$this->users()) {
            Application::message('MERGE_FAILED_USERS', Application::ERROR);

            return false;
        }

        return true;
    }

    /**
     * This resource is not referenced in schedule DIFs.
     * @return bool
     */
    protected function updateSchedules(): bool
    {
        return true;
    }

    /**
     * Updates relevant user table values and removes duplicate users.
     * @return bool
     */
    private function users(): bool
    {
        // activation, authProvider, otep, otpKey, params, password, requireReset, username will not be altered
        $block         = null;
        $groupIDs      = [];
        $lastResetTime = null;
        $lastvisitDate = null;
        $registerDate  = date('Y-m-d H:i:s');
        $resetCount    = 0;
        $user          = null;

        foreach ($this->mergeIDs as $mergeID) {

            // Joomla aggregates the user values from multiple tables in the user instance
            $instance = User::instance($mergeID);
            $table    = new Users();

            if (!$instance->id or !$table->load($mergeID) or $instance->id !== $table->id) {
                return false;
            }

            $block         = ($block or $instance->block);
            $groupIDs      = array_merge($groupIDs, $instance->groups);
            $lastResetTime = max($lastResetTime, $instance->lastResetTime);
            $lastvisitDate = max($lastvisitDate, $instance->lastvisitDate);
            $registerDate  = min($registerDate, $instance->registerDate);
            $resetCount    += (int) $instance->resetCount;

            if ($table->id === $this->mergeID) {
                $user = $table;
            }
            elseif (!$table->delete()) {
                return false;
            }
        }

        if (!$user) {
            return false;
        }

        $user->block         = (int) $block;
        $user->lastResetTime = $lastResetTime;
        $user->lastvisitDate = $lastvisitDate;
        $user->registerDate  = $registerDate;
        $user->resetCount    = $resetCount;
        $user->store();

        $this->groups($groupIDs);

        return true;
    }

    /** @inheritDoc */
    protected function validate(array &$data, array $required = []): void
    {
        parent::validate($data, ['email']);

        if (!empty($data['email'])) {
            $this->email = $data['email'];
        }
    }
}