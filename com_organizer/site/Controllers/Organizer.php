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
use Joomla\CMS\User\User;
use THM\Organizer\Adapters\{Application, Database as DB, Text};
use THM\Organizer\Helpers\Terms;

/** @inheritDoc */
class Organizer extends Controller
{
    private const DEPRECATED = -1, UNREFERENCED = 0;

    /**
     * Cleans various tables of inconsistency creep.
     * @return void
     */
    public function clean(): void
    {
        $this->checkToken();
        $this->authorize();

        if (JDEBUG) {
            Application::message('ORGANIZER_DEBUG_ON', Application::ERROR);

            $this->setRedirect("$this->baseURL&view=organizer");
            return;
        }

        $this->duplicateAssociations();
        $this->deprecatedPlanning();
        $this->inactivePeople();

        $this->setRedirect("$this->baseURL&view=organizer");
    }

    /**
     * Removes potential duplicates from the associations table resulting from MySQL's inability to use NULL values in unique
     * keys.
     * @return void
     */
    private function duplicateAssociations(): void
    {
        $conditions = [DB::qc('d.id', 'r.id', '>'), DB::qc('d.organizationID', 'r.organizationID')];

        $orConditions = [];
        foreach (['categoryID', 'groupID', 'personID', 'poolID', 'programID', 'subjectID'] as $column) {
            $andConditions  = [DB::qc("d.$column", "r.$column"), DB::qn("d.$column") . ' IS NOT NULL'];
            $orConditions[] = implode(' AND ', $andConditions);
        }

        $conditions[] = '((' . implode(') OR (', $orConditions) . '))';

        $query = DB::getQuery();
        $query->select(DB::qn('d.id'))
            ->from(DB::qn('#__organizer_associations', 'd'))
            ->from(DB::qn('#__organizer_associations', 'r'))
            ->where($conditions);
        DB::setQuery($query);

        if ($duplicateIDs = DB::loadIntColumn()) {
            $query = DB::getQuery();
            $query->delete(DB::qn('#__organizer_associations'))
                ->whereIn('id', $duplicateIDs);
            DB::setQuery($query);

            if (DB::execute()) {
                Application::message('X_DUPLICATE_ASSOCIATIONS_DELETED');
            }
            else {
                Application::message('X_DUPLICATE_ASSOCIATIONS_NOT_DELETED', Application::WARNING);
            }
        }
    }

    /**
     * Removes removed scheduling related resources and associations associated with terminated terms.
     * @return void
     */
    private function deprecatedPlanning(): void
    {
        $iTable = DB::qn('#__organizer_instances', 'i');
        $tag    = Application::tag();

        // Units unreferenced by instances.
        $query = DB::getQuery();
        $query->select('DISTINCT ' . DB::qn('u.id'))
            ->from(DB::qn('#__organizer_units', 'u'))
            ->leftJoin($iTable, DB::qc('i.unitID', 'u.id'))
            ->where(DB::qn('i.id') . ' IS NULL');
        DB::setQuery($query);

        if ($urUnitIDs = DB::loadIntColumn()) {
            $this->deleteResources('units', $urUnitIDs, $tag, 'UNITS', self::UNREFERENCED);
        }

        $query = DB::getQuery();
        $query->select(DB::qn('id'))->from(DB::qn('#__organizer_runs'))->where(DB::qn('endDate') . ' < CURDATE()');
        DB::setQuery($query);

        if ($dpRunIDs = DB::loadIntColumn()) {
            $this->deleteResources('runs', $dpRunIDs, $tag, 'RUNS', self::DEPRECATED);
        }

        if ($termIDs = Terms::expiredIDs()) {
            $query = DB::getQuery();
            $query->select(DB::qn('id'))->from(DB::qn('#__organizer_schedules'))->whereIn(DB::qn('termID'), $termIDs);
            DB::setQuery($query);

            if ($dpScheduleIDs = DB::loadIntColumn()) {
                $this->deleteResources('schedules', $dpScheduleIDs, $tag, 'SCHEDULES', self::DEPRECATED);
            }

            $query = DB::getQuery();
            $query->select(DB::qn('id'))
                ->from(DB::qn('#__organizer_units'))
                ->where(DB::qc('delta', 'removed', '=', true))
                ->whereIn(DB::qn('termID'), $termIDs);
            DB::setQuery($query);

            if ($dpUnitIDs = DB::loadIntColumn()) {
                $this->deleteResources('units', $dpUnitIDs, $tag, 'UNITS', self::DEPRECATED);
            }
        }

        // Expired and removed resources and associations.
        $aTable     = DB::qn('#__organizer_instance_persons', 'assoc');
        $bCondition = DB::qc('b.id', 'i.blockID');
        $bTable     = DB::qn('#__organizer_blocks', 'b');
        $dCondition = DB::qc('b.date', Terms::startDate(), '<', true);
        $iCondition = DB::qc('assoc.instanceID', 'i.id');

        $query = DB::getQuery();
        $query->select(DB::qn('i.id'))->from($iTable)->innerJoin($bTable, $bCondition)
            ->where([$dCondition, DB::qc('i.delta', 'removed', '=', true)]);
        DB::setQuery($query);

        if ($dpInstanceIDs = DB::loadIntColumn()) {
            $this->deleteResources('instances', $dpInstanceIDs, $tag, 'INSTANCES', self::DEPRECATED);
        }

        $query = DB::getQuery();
        $query->select(DB::qn('assoc.id'))->from($aTable)->innerJoin($iTable, $iCondition)->innerJoin($bTable, $bCondition)
            ->where([$dCondition, DB::qc('assoc.delta', 'removed', '=', true)]);
        DB::setQuery($query);

        if ($dpAssocIDs = DB::loadIntColumn()) {
            $this->deleteResources('instance_persons', $dpAssocIDs, $tag, 'INSTANCE_PERSONS', self::DEPRECATED);
        }

        $query = DB::getQuery();
        $query->select(DB::qn('ig.id'))->from(DB::qn('#__organizer_instance_groups', 'ig'))
            ->innerJoin($aTable, DB::qc('assoc.id', 'ig.assocID'))->innerJoin($iTable, $iCondition)
            ->innerJoin($bTable, $bCondition)->where([$dCondition, DB::qc('ig.delta', 'removed', '=', true)]);
        DB::setQuery($query);

        if ($dpGroupIDs = DB::loadIntColumn()) {
            $this->deleteResources('instance_groups', $dpGroupIDs, $tag, 'INSTANCE_GROUPS', self::DEPRECATED);
        }

        $query = DB::getQuery();
        $query->select(DB::qn('ir.id'))->from(DB::qn('#__organizer_instance_rooms', 'ir'))->innerJoin($aTable,
            DB::qc('assoc.id', 'ir.assocID'))
            ->innerJoin($iTable, $iCondition)->innerJoin($bTable, $bCondition)
            ->where([$dCondition, DB::qc('ir.delta', 'removed', '=', true)]);
        DB::setQuery($query);

        if ($dpRoomIDs = DB::loadIntColumn()) {
            $this->deleteResources('instance_rooms', $dpRoomIDs, $tag, 'INSTANCE_ROOMS', self::DEPRECATED);
        }

        $iCondition = DB::qn('i.id') . ' IS NULL';

        // Unreferenced blocks
        $query = DB::getQuery();
        $query->select(DB::qn('b.id'))->from($bTable)->leftJoin($iTable, DB::qc('i.blockID', 'b.id'))
            ->where([$dCondition, $iCondition]);
        DB::setQuery($query);

        if ($urBlockIDs = DB::loadIntColumn()) {
            $this->deleteResources('blocks', $urBlockIDs, $tag, 'BLOCKS', self::UNREFERENCED);
        }

        // Unreferenced events
        $query = DB::getQuery();
        $query->select(DB::qn('e.id'))->from(DB::qn('#__organizer_events', 'e'))->leftJoin($iTable, DB::qc('i.eventID', 'e.id'))
            ->where($iCondition);
        DB::setQuery($query);

        if ($urEventIDs = DB::loadIntColumn()) {
            $this->deleteResources('events', $urEventIDs, $tag, 'EVENTS', self::UNREFERENCED);
        }

        $query = DB::getQuery();
        $query->select(DB::qn('p.id'))
            ->from(DB::qn('#__organizer_persons', 'p'))
            ->leftJoin(DB::qn('#__organizer_event_coordinators', 'ec'), DB::qc('ec.personID', 'p.id'))
            ->leftJoin(DB::qn('#__organizer_instance_persons', 'ip'), DB::qc('ip.personID', 'p.id'))
            ->leftJoin(DB::qn('#__organizer_subject_persons', 'sp'), DB::qc('sp.personID', 'p.id'))->where([
                DB::qn('ec.id') . ' IS NULL',
                DB::qn('ip.id') . ' IS NULL',
                DB::qn('sp.id') . ' IS NULL'
            ]);
        DB::setQuery($query);

        if ($urPersonIDs = DB::loadIntColumn()) {
            $this->deleteResources('persons', $urPersonIDs, $tag, 'PERSONS', self::UNREFERENCED);
        }
    }

    /**
     * Deletes entries from a resource table by id.
     *
     * @param   string  $table        the table to delete from
     * @param   array   $resourceIDs  the ids of the entries to delete
     * @param   string  $tag          the code for the active language
     * @param   string  $constant     the localization key
     * @param   int     $reason       the reason for deletion
     *
     * @return void
     */
    private function deleteResources(string $table, array $resourceIDs, string $tag, string $constant, int $reason): void
    {
        $count     = count($resourceIDs);
        $resources = $tag === 'en' ? strtolower(Text::_($constant)) : ucfirst(strtolower(Text::_($constant)));
        $query     = DB::getQuery();
        $query->delete(DB::qn("#__organizer_$table"))->whereIn(DB::qn('id'), $resourceIDs);
        DB::setQuery($query);

        $adjective = $reason === self::DEPRECATED ? 'DEPRECATED' : 'UNREFERENCED';

        if (DB::execute()) {
            Application::message(Text::sprintf("X_{$adjective}_X_DELETED", $count, $resources));
        }
        else {
            Application::message(Text::sprintf("X_{$adjective}_X_NOT_DELETED", $count, $resources), Application::WARNING);
        }
    }

    /**
     * Removes person related resources due to inactivity or irrelevance.
     * @return void
     */
    private function inactivePeople(): void
    {
        if (Application::parameters('plg_user_joomla')->get('mail_to_user')) {
            Application::message('USER_PLUGIN_NOTIFICATIONS_ACTIVE', Application::WARNING);
            return;
        }

        $cutOff      = date('Y-m-d H:i:s', strtotime(Terms::startDate(Terms::previousID())));
        $cutOff      = DB::qc('u.lastvisitDate', $cutOff, '<', true);
        $pConditions = DB::qc('p.id', 'u.id');
        $pTable      = DB::qn('#__organizer_participants', 'p');
        $uID         = DB::qn('u.id');
        $uTable      = DB::qn('#__users', 'u');

        // Inactive users that do not have entries in the participants table.
        $query = DB::getQuery();
        $query->select($uID)->from($uTable)->leftJoin($pTable, $pConditions)->where([DB::qn('p.id') . ' IS NULL', $cutOff]);
        DB::setQuery($query);

        $this->purgeUsers(DB::loadIntColumn(), Text::_('USERS_WITHOUT_PROFILES'));

        // Inactive users who have no course or instance participation data.
        $query = DB::getQuery();
        $query->select($uID)->from($uTable)->innerJoin($pTable, $pConditions)
            ->leftJoin(DB::qn('#__organizer_course_participants', 'cp'), DB::qc('cp.participantID', 'p.id'))
            ->leftJoin(DB::qn('#__organizer_instance_participants', 'ip'), DB::qc('ip.participantID', 'p.id'))
            ->where([DB::qn('cp.id') . ' IS NULL', DB::qn('ip.id') . ' IS NULL', $cutOff]);
        DB::setQuery($query);

        $this->purgeUsers(DB::loadIntColumn(), Text::_('USERS_WITHOUT_PARTICIPATION'));
    }

    /**
     * Deletes a user contingent. Will not delete if the user is assigned to groups other than registered.
     *
     * @param   int[]   $userIDs        the ids of the users to delete
     * @param   string  $qualifiedNoun  the adjective/noun combination describing the users being purged
     *
     * @return void
     */
    private function purgeUsers(array $userIDs, string $qualifiedNoun): void
    {
        $count      = count($userIDs);
        $deleted    = 0;
        $protected  = 0;
        $registered = 2;

        foreach ($userIDs as $userID) {
            $user = User::getInstance($userID);

            // $user->groups set with ArrayHelper::toInteger on line 285 2024-07-10
            if (count($user->groups) === 1 and reset($user->groups) === $registered) {
                $user->delete();
                $deleted++;
                continue;
            }

            $protected++;
        }

        // Suppress 0 messages.
        if ($deleted) {
            // Best case all (unprotected) users were deleted
            if ($deleted === $count or ($deleted + $protected) === $count) {
                Application::message(Text::sprintf("X_X_DELETED", $deleted, Text::_($qualifiedNoun)));
            }
            // Not all users were deleted and protected users do not make up the difference.
            elseif ($protected !== $count) {
                Application::message(
                    Text::sprintf("X_OF_X_X_DELETED", $deleted, $count, Text::_($qualifiedNoun)),
                    Application::WARNING
                );
            }
        }

        if ($protected) {
            Application::message(Text::sprintf("X_X_PROTECTED_NOT_DELETED", $protected, Text::_($qualifiedNoun)),
                Application::NOTICE);
        }
    }

    /**
     * Creates a new booking element for a given instance and redirects to the corresponding instance participants view.
     * @return void
     */
    public function reKey(): void
    {
        $this->checkToken();
        $this->authorize();

        /**
         * The tables which will be iterated for processing.
         * Ignored tables:
         * -campuses, categories, methods & programs are referenced externally from string values in the menu table
         * -curricula are self referencing and have fk delete mechanisms
         * -equipment, room_equipment is currently in development
         * -frequencies, roles, roomkeys, use_codes & use_groups are static values
         * -groups, instances, persons, roles & rooms are referenced in a JSON string value in the schedules table
         * -monitors small entry number tables
         * -organizations are referenced externally from string values in the assets table
         * -participants are fk references to the users table
         */
        $compatibles = [
            'associations',
            'blocks',
            'bookings',
            'buildings',
            'cleaning_groups',
            'colors',
            'course_participants',
            'courses',
            'degrees',
            'event_coordinators',
            'events',
            'field_colors',
            'fields',
            'flooring',
            'grids',
            'group_publishing',
            'holidays',
            'instance_groups',
            'instance_participants',
            'instance_persons',
            'instance_rooms',
            'pools',
            'prerequisites',
            'roomtypes',
            'runs',
            'schedules',
            'subject_events',
            'subject_persons',
            'subjects',
            'terms',
            'units'
        ];

        try {
            foreach ($compatibles as $table) {
                DB::setQuery('SET @count = 0');
                DB::execute();

                DB::setQuery("UPDATE #__organizer_$table SET id = @count:= @count + 1");
                DB::execute();

                DB::setQuery("ALTER TABLE #__organizer_$table AUTO_INCREMENT = 1");
                DB::execute();
            }
        }
        catch (Exception $exception) {
            Application::message($exception->getMessage(), Application::ERROR);
        }

        Application::message('TABLES_REKEYED');
        $this->setRedirect("$this->baseURL&view=organizer");
    }
}
