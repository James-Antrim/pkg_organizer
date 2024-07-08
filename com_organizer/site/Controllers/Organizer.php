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

use Joomla\CMS\Router\Route;
use Joomla\CMS\User\User;
use THM\Organizer\Adapters\{Application, Database as DB, Text};
use THM\Organizer\Helpers\{Routing, Terms};
use THM\Organizer\Models;

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

        $this->duplicateAssociations();
        $this->deprecatedPlanning();
        //$this->inactivePeople();

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
        $tag    = Application::getTag();

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
        if (Application::getParams('plg_user_joomla')->get('mail_to_user')) {
            Application::message('USER_PLUGIN_NOTIFICATIONS_ACTIVE', Application::WARNING);
            return;
        }

        $cutOff = date('Y-m-d H:i:s', strtotime(Terms::startDate()));

        // Past users that were never participants.
        $query = DB::getQuery();
        $query->select(DB::qn('u.id'))->from(DB::qn('#__users'))
            ->leftJoin(DB::qn('#__organizer_participants', 'p'), DB::qc('p.id', 'u.id'))
            ->where([DB::qn('p.id') . ' IS NULL', DB::qc('u.lastvisitDate', $cutOff, '<', true)]);
        DB::setQuery($query);


    }

    /**
     * Deletes a user contingent. Will not delete if the user is assigned to groups other than registered.
     *
     * @param   int[]  $userIDs  the ids of the users to delete
     *
     * @return int
     */
    private function purgeUsers(array $userIDs): int
    {
        $deleted    = 0;
        $registered = 2;

        foreach ($userIDs as $zombieID) {
            // Let Joomla perform its normal user delete process.
            $user    = User::getInstance($zombieID);
            $groups  = $user->groups;
            $single  = count($groups) === 1;
            $groupID = (int) array_pop($groups);

            if ($single and $groupID === $registered) {
                $user->delete();
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Creates a new booking element for a given instance and redirects to the corresponding instance participants view.
     * @return void
     */
    public function reKeyTables(): void
    {
        $model = new Models\Organizer();
        $model->reKeyTables();
        $url = Routing::getRedirectBase() . "&view=organizer";
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Updates all instance participation numbers.
     * @return void
     */
    public function updateNumbers(): void
    {
        $model = new Models\Instance();
        $model->updateNumbers();
        $url = Routing::getRedirectBase() . "&view=organizer";
        $this->setRedirect(Route::_($url, false));
    }
}
