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

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input as JInput;
use THM\Organizer\Adapters\{Application, Database as DB, Database, Input};
use THM\Organizer\Tables\{Associations, InstanceGroups, InstanceRooms, Schedules};

/**
 * @inheritDoc
 */
abstract class MergeController extends FormController
{
    protected string $mergeContext = '';
    protected array $deprecatedIDs;
    public int $mergeID;
    public array $mergeIDs;

    /**
     * @inheritDoc
     */
    public function __construct($config = [],
        MVCFactoryInterface $factory = null,
        ?CMSApplication $app = null,
        ?JInput $input = null
    )
    {
        if (empty($this->mergeContext)) {
            Application::error(501);
        }

        parent::__construct($config, $factory, $app, $input);
    }

    /**
     * @inheritDoc
     */
    public function display($cachable = false, $urlparams = []): BaseController
    {
        if (JDEBUG) {
            Application::message('ORGANIZER_DEBUG_ON', Application::ERROR);

            $this->setRedirect("$this->baseURL&view=$this->list");
        }

        $this->input->set('view', Application::getClass($this));

        return parent::display($cachable, $urlparams);
    }

    /**
     * Gets the resource ids associated with persons in association tables.
     *
     * @param   string  $table     the unique portion of the table name
     * @param   string  $fkColumn  the name of the fk column referencing the other resource
     *
     * @return int[] the ids of the resources associated
     */
    protected function getReferences(string $table, string $fkColumn): array
    {
        $refColumn = $this->mergeContext . 'ID';

        [$table, $refColumn, $fkColumn] = DB::qn(["#__organizer_$table", $refColumn, $fkColumn]);

        $query = Database::getQuery();
        $query->select("DISTINCT $fkColumn")->from($table)->whereIn($refColumn, $this->mergeIDs)->order($fkColumn);
        Database::setQuery($query);

        return Database::loadIntColumn();
    }

    /**
     * @inheritDoc
     */
    protected function prepareData(): array
    {
        $data       = parent::prepareData();
        $data['id'] = $this->mergeID;
        $this->validate($data);
        return $data;
    }

    /**
     * Code common in storing resource data.
     * @return int
     */
    protected function process(): int
    {
        $this->checkToken();
        $this->authorize();

        $this->resolveIDs();

        /**
         * Parent makes redundant authorize/token checks and can mess up the merge ID.
         */
        $data = $this->prepareData();

        // Associations have to be updated before entity references are deleted by foreign keys
        if (!$this->updateReferences()) {
            return 0;
        }
        elseif (!$this->updateSchedules()) {
            return 0;
        }
        else {
            foreach ($this->deprecatedIDs as $deprecatedID) {
                $table = $this->getTable();

                // Tables whose IDs are fk references should already be gone through updateReferences.
                if (!$table->load($deprecatedID)) {
                    continue;
                }

                $table->delete();
            }

            $table = $this->getTable();

            if ($result = $this->store($table, $data, $data['id'])) {
                Application::message('SAVED');
            }
            else {
                Application::message('NOT_SAVED');
            }
            return $result;
        }
    }

    /**
     * Resolves the incoming ids for later ease of use.
     * @return void
     */
    protected function resolveIDs(): void
    {
        $ids = Input::getIntCollection('ids');
        asort($ids);

        $this->mergeIDs      = $ids;
        $this->mergeID       = array_shift($ids);
        $this->deprecatedIDs = $ids;
    }

    /**
     * Updates an instance person association with groups, persons or rooms.
     * @return bool
     */
    protected function updateAssignments(): bool
    {
        $column = $this->mergeContext . 'ID';
        $table  = DB::qn('#__organizer_instance_' . $this->mergeContext . 's');
        $query  = DB::getQuery();
        $query->select('*')
            ->from($table)
            ->whereIn(DB::qn($column), $this->mergeIDs)
            ->order(DB::qn(['assocID', 'modified']));
        DB::setQuery($query);

        if (!$results = DB::loadAssocList()) {
            return true;
        }

        $initialSize = count($results);
        $nextIndex   = 0;
        $tableClass  = "THM\\Organizer\\Tables\\Instance" . $this->list;

        for ($index = 0; $index < $initialSize;) {

            /** @var InstanceGroups|InstanceRooms $assocTable */
            $assocTable = new $tableClass();
            $thisAssoc  = $results[$index];
            $nextIndex  = $nextIndex ?: $index + 1;
            $nextAssoc  = empty($results[$nextIndex]) ? [] : $results[$nextIndex];

            // Unique IP association.
            if (empty($nextAssoc) or $thisAssoc['assocID'] !== $nextAssoc['assocID']) {

                $assocTable->load($thisAssoc['id']);
                $assocTable->$column = $this->mergeID;
                $assocTable->store();

                $index++;
                $nextIndex++;
                continue;
            }

            /* Non-unique IP associations. */

            // Redundant association
            if ($thisAssoc['delta'] === 'removed' or $nextAssoc['delta'] !== 'removed') {
                $assocTable->delete($thisAssoc['id']);
                $index++;
                $nextIndex++;
                continue;
            }

            // Remove removed entries added later
            do {
                $assocTable->delete($nextAssoc['id']);
                unset($results[$nextIndex]);

                $nextIndex++;
                $nextAssoc = $results[$nextIndex];

                // This is the last result associated with the current IP association.
                if ($thisAssoc['assocID'] !== $nextAssoc['assocID']) {
                    $assocTable->load($thisAssoc['id']);
                    $assocTable->$column = $this->mergeID;
                    $assocTable->store();
                    $index = $nextIndex;
                    $nextIndex++;
                    continue 2;
                }

                // An IP association added later is still current.
                if ($nextAssoc['delta'] !== 'removed') {
                    $assocTable->delete($thisAssoc['id']);
                    $index = $nextIndex;
                    $nextIndex++;
                    continue 2;
                }
            }
            while (true);
        }

        return true;
    }

    /**
     * Updates associations entries for the resource in the merge context. Not to be confused with updateAssociations in
     * the save context.
     *
     * @return bool
     * @see Associated::updateAssociations()
     */
    protected function updateAssociations(): bool
    {
        $fkColumn = $this->mergeContext . 'ID';

        $query = DB::getQuery();
        $query->select(DB::qn('organizationID'))
            ->from(DB::qn("#__organizer_associations"))
            ->whereIn(DB::qn($fkColumn), $this->mergeIDs)
            ->order(DB::qn('organizationID'));
        DB::setQuery($query);

        if (!$organizationIDs = DB::loadIntColumn()) {
            return true;
        }

        $keys = [$fkColumn => $this->mergeID];

        foreach ($organizationIDs as $organizationID) {

            $table                  = new Associations();
            $keys['organizationID'] = $organizationID;

            if ($table->load($keys)) {
                continue;
            }

            $table->store($keys);
        }

        return true;
    }

    /**
     * Updates resource associations in a schedule instance.
     *
     * @param   array  &$instance  the instance being iterated
     * @param   int     $mergeID   the id onto which the entries will be merged
     *
     * @return bool true if the instance has been updated, otherwise false
     */
    private function updateInstance(array &$instance, int $mergeID): bool
    {
        $context  = $this->mergeContext . 's';
        $relevant = false;

        foreach ($instance as $personID => $resources) {
            // Array intersect keeps relevant keys from array one.
            if (!$relevantResources = array_intersect($resources[$context], $this->mergeIDs)) {
                continue;
            }

            $relevant = true;

            // Unset all relevant indexes to avoid conditional/unique handling
            foreach (array_keys($relevantResources) as $relevantIndex) {
                unset($instance[$personID][$context][$relevantIndex]);
            }

            // Put the merge id in/back in
            $instance[$personID][$context][] = $mergeID;

            // Re-sequence to avoid JSON encoding treating the array as associative (object)
            $instance[$personID][$context] = array_values($instance[$personID][$context]);
        }

        return $relevant;
    }

    /**
     * Updates the resource dependent associations.
     * @return bool
     */
    abstract protected function updateReferences(): bool;

    /**
     * Updates resource associations in a schedule.
     *
     * @param   int  $scheduleID  the id of the schedule being iterated
     *
     * @return void
     */
    private function updateSchedule(int $scheduleID): void
    {
        $schedule = new Schedules();

        if (!$schedule->load($scheduleID)) {
            return;
        }

        $instances = json_decode($schedule->schedule, true);
        $relevant  = false;

        foreach ($instances as $instanceID => $instance) {
            if (in_array($this->mergeContext, ['group', 'room']) and $this->updateInstance($instance, $this->mergeID)) {
                $instances[$instanceID] = $instance;
                $relevant               = true;
            } // Person
            else {
                if (!$relevantPersons = array_intersect(array_keys($instance), $this->mergeIDs)) {
                    continue;
                }

                $relevant = true;
                ksort($relevantPersons);

                // Use the associations of the maximum personID (last added)
                $associations = [];

                foreach ($relevantPersons as $personID) {
                    $associations = $instances[$instanceID][$personID];
                    unset($instances[$instanceID][$personID]);
                }

                $instances[$instanceID][$this->mergeID] = $associations;
            }
        }

        if ($relevant) {
            $schedule->schedule = json_encode($instances);
            $schedule->store();
        }
    }

    /**
     * Updates resource associations in stored json schedule DIFs.
     * @return bool
     */
    protected function updateSchedules(): bool
    {
        $query = DB::getQuery();
        $query->select(DB::qn('id'))->from(DB::qn('#__organizer_schedules'));
        DB::setQuery($query);

        if (!$scheduleIDs = DB::loadIntColumn()) {
            return true;
        }

        foreach ($scheduleIDs as $scheduleID) {
            $this->updateSchedule($scheduleID);
        }

        return true;
    }

    /**
     * Updates an association where the associated resource itself has a fk reference to the resource being merged.
     *
     * @param   string  $table  the unique part of the table name
     *
     * @return bool
     */
    protected function updateTable(string $table): bool
    {
        $column = DB::qn($this->mergeContext . 'ID');
        $query  = Database::getQuery();
        $query->update("#__organizer_$table")
            ->set("$column = :mergeID")->bind(':mergeID', $this->mergeID)
            ->whereIn($column, $this->mergeIDs);
        Database::setQuery($query);

        return Database::execute();
    }
}