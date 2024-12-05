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
use Joomla\Database\DatabaseQuery;
use THM\Organizer\Adapters\{Application, Database as DB, Text};
use THM\Organizer\Helpers\Terms;

/** @inheritDoc */
class Organizer extends Controller
{
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

        $query = DB::query();
        $query->select(DB::qn('d.id'))
            ->from(DB::qn('#__organizer_associations', 'd'))
            ->from(DB::qn('#__organizer_associations', 'r'))
            ->where($conditions);
        DB::set($query);

        if ($duplicateIDs = DB::integers()) {
            $query = DB::query();
            $query->delete(DB::qn('#__organizer_associations'))
                ->whereIn('id', $duplicateIDs);
            DB::set($query);

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
        // Runs
        $query = DB::query();
        $query->delete(DB::qn('#__organizer_runs'))->where(DB::qn('endDate') . ' < CURDATE()');
        $this->executeStatement($query, 'DEPRECATED', 'RUNS');

        // Schedules
        $query = DB::query();
        $query->delete(DB::qn('#__organizer_schedules'))->whereIn(DB::qn('termID'), Terms::expiredIDs());
        $this->executeStatement($query, 'DEPRECATED', 'SCHEDULES');

        $conditions   = ['delta', 'removed', '=', true];
        $iTable       = DB::qn('#__organizer_instances', 'i');
        $unreferenced = DB::qn('i.id') . ' IS NULL';
        $uTable       = DB::qn('#__organizer_units', 'u');

        // Removed units from expired terms
        $query = DB::query();
        $query->delete(DB::qn('#__organizer_units'))
            ->where(DB::qcs([$conditions]))->whereIn(DB::qn('termID'), Terms::expiredIDs());
        $this->executeStatement($query, 'DEPRECATED', 'UNITS');

        // Units unreferenced by instances
        $query = DB::query();
        $query->select('u.id')->from($uTable)->leftJoin($iTable, DB::qc('i.unitID', 'u.id'))->where($unreferenced);
        $query = $this->prepareStatement($query, $uTable, 'u');
        $this->executeStatement($query, 'UNREFERENCED', 'UNITS');

        $bConditions  = DB::qc('b.id', 'i.blockID');
        $bTable       = DB::qn('#__organizer_blocks', 'b');
        $eTable       = DB::qn('#__organizer_events', 'e');
        $edConditions = ['date', Terms::endDate(Terms::previousID()), '<', true];
        $iConditions  = DB::qc('i.id', 'ip.instanceID');
        $iTable       = DB::qn('#__organizer_instances', 'i');
        $igTable      = DB::qn('#__organizer_instance_groups', 'ig');
        $ipTable      = DB::qn('#__organizer_instance_persons', 'ip');
        $irTable      = DB::qn('#__organizer_instance_rooms', 'ir');

        // Removed instances from expired terms
        $query = DB::query();
        $query->select('i.id')->from($iTable)->innerJoin($bTable, $bConditions)
            ->where(DB::qcs([$edConditions, $conditions]));
        $query = $this->prepareStatement($query, $iTable, 'i');
        $this->executeStatement($query, 'DEPRECATED', 'INSTANCES');

        // Blocks unreferenced by instances
        $query = DB::query();
        $query->select('b.id')->from($bTable)->leftJoin($iTable, DB::qc('i.blockID', 'b.id'))->where($unreferenced);
        $query = $this->prepareStatement($query, $bTable, 'b');
        $this->executeStatement($query, 'UNREFERENCED', 'BLOCKS');

        // Events unreferenced by instances
        $query = DB::query();
        $query->select('e.id')->from($eTable)->leftJoin($iTable, DB::qc('i.eventID', 'e.id'))->where($unreferenced);
        $query = $this->prepareStatement($query, $eTable, 'e');
        $this->executeStatement($query, 'UNREFERENCED', 'EVENTS');

        // Removed instance => person relationships from expired terms
        $query = DB::query();
        $query->select('ip.id')->from($ipTable)->innerJoin($iTable, $iConditions)->innerJoin($bTable, $bConditions)
            ->where(DB::qcs([$edConditions, ['ip.delta', 'removed', '=', true]]));
        $query = $this->prepareStatement($query, $ipTable, 'ip');
        $this->executeStatement($query, 'DEPRECATED', 'INSTANCE_PERSONS');

        // Removed instance => person => group relationships from expired terms
        $query = DB::query();
        $query->select('ig.id')->from($igTable)
            ->innerJoin($ipTable, DB::qc('ip.id', 'ig.assocID'))
            ->innerJoin($iTable, $iConditions)->innerJoin($bTable, $bConditions)
            ->where(DB::qcs([$edConditions, ['ig.delta', 'removed', '=', true]]));
        $query = $this->prepareStatement($query, $igTable, 'ig');
        $this->executeStatement($query, 'DEPRECATED', 'INSTANCE_GROUPS');

        // Removed instance => person => room relationships from expired terms
        $query = DB::query();
        $query->select('ir.id')->from($irTable)
            ->innerJoin($ipTable, DB::qc('ip.id', 'ir.assocID'))
            ->innerJoin($iTable, $iConditions)->innerJoin($bTable, $bConditions)
            ->where(DB::qcs([$edConditions, ['ir.delta', 'removed', '=', true]]));
        $query = $this->prepareStatement($query, $irTable, 'ir');
        $this->executeStatement($query, 'DEPRECATED', 'INSTANCE_ROOMS');

        $pTable = DB::qn('#__organizer_persons', 'p');

        // Persons unreferenced by any referencing table
        $query = DB::query();
        $query->select('p.id')->from($pTable)
            ->leftJoin(DB::qn('#__organizer_event_coordinators', 'ec'), DB::qc('ec.personID', 'p.id'))
            ->leftJoin(DB::qn('#__organizer_instance_persons', 'ip'), DB::qc('ip.personID', 'p.id'))
            ->leftJoin(DB::qn('#__organizer_subject_persons', 'sp'), DB::qc('sp.personID', 'p.id'))
            ->where([DB::qn('ec.id') . ' IS NULL', DB::qn('ip.id') . ' IS NULL', DB::qn('sp.id') . ' IS NULL']);
        $query = $this->prepareStatement($query, $pTable, 'p');
        $this->executeStatement($query, 'UNREFERENCED', 'PERSONS');
    }

    /**
     * Executes a delete statement on a prepared query statement and creates a feedback message as applicable.
     *
     * @param   DatabaseQuery|string  $query      the query to be executed
     * @param   string                $adjective  the reason for resource deletion
     * @param   string                $resource   the resource being deleted
     *
     * @return void
     */
    private function executeStatement(DatabaseQuery|string $query, string $adjective, string $resource): void
    {
        DB::set($query);
        DB::execute();

        if ($affected = DB::affected()) {
            Application::message(Text::sprintf("X_{$adjective}_X_DELETED", $affected, Text::_($resource)));
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
        $query = DB::query();
        $query->select($uID)->from($uTable)->leftJoin($pTable, $pConditions)->where([DB::qn('p.id') . ' IS NULL', $cutOff]);
        DB::set($query);

        $this->purgeUsers(DB::integers(), Text::_('USERS_WITHOUT_PROFILES'));

        // Inactive users who have no course or instance participation data.
        $query = DB::query();
        $query->select($uID)->from($uTable)->innerJoin($pTable, $pConditions)
            ->leftJoin(DB::qn('#__organizer_course_participants', 'cp'), DB::qc('cp.participantID', 'p.id'))
            ->leftJoin(DB::qn('#__organizer_instance_participants', 'ip'), DB::qc('ip.participantID', 'p.id'))
            ->where([DB::qn('cp.id') . ' IS NULL', DB::qn('ip.id') . ' IS NULL', $cutOff]);
        DB::set($query);

        $this->purgeUsers(DB::integers(), Text::_('USERS_WITHOUT_PARTICIPATION'));
    }

    /**
     * Creates a joined deletion query string based on a select query object.
     *
     * @param   DatabaseQuery  $query  the source query
     * @param   string         $table  the table to delete from
     * @param   string         $alias  the table alias
     *
     * @return string
     */
    private function prepareStatement(DatabaseQuery $query, string $table, string $alias): string
    {
        $query->clear('select')->clear('from')->delete($table);
        $query = (string) $query;
        return str_replace('DELETE ', 'DELETE ' . DB::qn($alias), $query);
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
                DB::set('SET @count = 0');
                DB::execute();

                DB::set("UPDATE #__organizer_$table SET id = @count:= @count + 1");
                DB::execute();

                DB::set("ALTER TABLE #__organizer_$table AUTO_INCREMENT = 1");
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
