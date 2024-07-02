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
        $instances = DB::qn('#__organizer_instances', 'i');

        // Units unreferenced by instances.
        $query = DB::getQuery();
        $query->select('DISTINCT ' . DB::qn('u.id'))
            ->from(DB::qn('#__organizer_units', 'u'))
            ->leftJoin($instances, DB::qc('i.unitID', 'u.id'))
            ->where(DB::qn('i.id') . ' IS NULL');
        DB::setQuery($query);

        if ($urUnitIDs = DB::loadIntColumn()) {
            $count = count($urUnitIDs);
            $query = DB::getQuery();
            $query->delete(DB::qn('#__organizer_units'))->whereIn(DB::qn('id'), $urUnitIDs);
            DB::setQuery($query);

            if (DB::execute()) {
                Application::message(Text::sprintf('X_UNREFERENCED_UNITS_DELETED', $count));
            }
            else {
                Application::message(Text::sprintf('X_UNREFERENCED_UNITS_NOT_DELETED', $count), Application::WARNING);
            }
        }

        return;

        $query = DB::getQuery();
        $query->select(DB::qn('id'))->from(DB::qn('#__organizer_runs'))->where(DB::qn('endDate') . ' < CURDATE()');
        DB::setQuery($query);
        DB::execute();

        if ($dpRunIDs = DB::loadIntColumn()) {
            echo "<pre>" . count($dpRunIDs) . "deprecated run ids: " . print_r($dpRunIDs, true) . "</pre>";
            $query = DB::getQuery();
            $query->delete(DB::qn('#__organizer_runs'))->whereIn(DB::qn('id'), $unreferencedIDs);
            DB::setQuery($query);
            DB::execute();
        }


        if ($termIDs = Terms::expiredIDs()) {
            $query = DB::getQuery();
            $query->delete(DB::qn('#__organizer_schedules'))->whereIn(DB::qn('termID'), $termIDs);
            DB::setQuery($query);
            DB::execute();

            $query = DB::getQuery();
            $query->delete(DB::qn('#__organizer_units'))
                ->where(DB::qc('delta', 'removed', '=', true))
                ->whereIn(DB::qn('termID'), Terms::expiredIDs());
            DB::setQuery($query);
            DB::execute();
        }

        // Expired and removed resources and associations.
        $associations = DB::qn('#__organizer_instance_persons', 'assoc');
        $bCondition   = DB::qc('b.id', 'i.blockID');
        $blocks       = DB::qn('#__organizer_blocks', 'b');
        $dCondition   = DB::qc('b.date', Terms::startDate(), '<', true);
        $iCondition   = DB::qc('assoc.instanceID', 'i.id');

        $query = DB::getQuery();
        $query->delete($instances)->innerJoin($blocks, $bCondition)
            ->where([$dCondition, DB::qc('i.delta', 'removed', '=', true)]);
        DB::setQuery($query);
        DB::execute();

        $query = DB::getQuery();
        $query->delete($associations)
            ->innerJoin($instances, $iCondition)->innerJoin($blocks, $bCondition)
            ->where([$dCondition, DB::qc('assoc.delta', 'removed', '=', true)]);
        DB::setQuery($query);
        DB::execute();

        $query = DB::getQuery();
        $query->delete(DB::qn('#__organizer_instance_groups', 'ig'))
            ->innerJoin($associations, DB::qc('assoc.id', 'ig.assocID'))
            ->innerJoin($instances, $iCondition)->innerJoin($blocks, $bCondition)
            ->where([$dCondition, DB::qc('ig.delta', 'removed', '=', true)]);
        DB::setQuery($query);
        DB::execute();

        $query = DB::getQuery();
        $query->delete(DB::qn('#__organizer_instance_rooms', 'ir'))
            ->innerJoin($associations, DB::qc('assoc.id', 'ir.assocID'))
            ->innerJoin($instances, $iCondition)->innerJoin($blocks, $bCondition)
            ->where([$dCondition, DB::qc('ir.delta', 'removed', '=', true)]);
        DB::setQuery($query);
        DB::execute();

        $iCondition = DB::qn('i.id') . ' IS NULL';

        // Unreferenced blocks
        $query = DB::getQuery();
        $query->delete($blocks)->leftJoin($instances, DB::qc('i.blockID', 'b.id'))
            ->where([$dCondition, $iCondition]);
        DB::setQuery($query);
        DB::execute();

        // Unreferenced events
        $query = DB::getQuery();
        $query->delete(DB::qn('#__organizer_events', 'e'))->leftJoin($instances, DB::qc('i.eventID', 'e.id'))
            ->where([$dCondition, $iCondition]);
        DB::setQuery($query);
        DB::execute();
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
