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

use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use THM\Organizer\Adapters\{Application, Database, Queries\QueryMySQLi};
use Joomla\Database\ParameterType;
use THM\Organizer\Helpers\Terms;
use THM\Organizer\Tables;

/**
 * Class which sets permissions for the view.
 */
class Organizer extends BaseModel
{
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
     * @todo add flooring management
     * @todo remove flooring insert values
     * @todo dynamically add flooring types during room import process
     * @var string[]
     */
    private $compacTables = [
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

    /**
     * Compensates for MySQL's inability to correctly use NULL values in unique keys by deleting duplicate associations
     * entries.
     * @return void
     */
    private function cleanAssociations()
    {
        $select     = ['duplicate.id'];
        $from       = ['associations AS duplicate', 'associations AS reference'];
        $conditions = [
            Database::qn('duplicate.id') . ' > ' . Database::qn('reference.id'),
            Database::qn('duplicate.organizationID') . ' = ' . Database::qn('reference.organizationID')
        ];

        $orConditions   = [];
        $orConditions[] = [
            Database::qn('duplicate.categoryID') . ' = ' . Database::qn('reference.categoryID'),
            Database::qn('duplicate.categoryID') . ' IS NOT NULL'
        ];
        $orConditions[] = [
            Database::qn('duplicate.groupID') . ' = ' . Database::qn('reference.groupID'),
            Database::qn('duplicate.groupID') . ' IS NOT NULL'
        ];
        $orConditions[] = [
            Database::qn('duplicate.personID') . ' = ' . Database::qn('reference.personID'),
            Database::qn('duplicate.personID') . ' IS NOT NULL'
        ];
        $orConditions[] = [
            Database::qn('duplicate.poolID') . ' = ' . Database::qn('reference.poolID'),
            Database::qn('duplicate.poolID') . ' IS NOT NULL'
        ];
        $orConditions[] = [
            Database::qn('duplicate.programID') . ' = ' . Database::qn('reference.programID'),
            Database::qn('duplicate.programID') . ' IS NOT NULL'
        ];
        $orConditions[] = [
            Database::qn('duplicate.subjectID') . ' = ' . Database::qn('reference.subjectID'),
            Database::qn('duplicate.subjectID') . ' IS NOT NULL'
        ];
        foreach ($orConditions as &$andConditions) {
            $andConditions = implode(' AND ', $andConditions);
        }
        $conditions[] = '((' . implode(') OR (', $orConditions) . '))';


        /* @var QueryMySQLi $query */
        $query = Database::getQuery();
        $query->selectX($select, $from)
            ->where($conditions);
        Database::setQuery($query);

        if ($duplicateIDs = Database::loadIntColumn()) {
            /* @var QueryMySQLi $query */
            $query = Database::getQuery();
            $query->deleteX('associations', 'id', $duplicateIDs);
            Database::setQuery($query);
            Database::execute();
        }
    }

    /**
     * Executes functions to clean database tables.
     * @return void
     */
    public function cleanDB()
    {
        $this->cleanAssociations();
        $this->cleanDeprecated();
        $this->cleanPeople();

        Application::message('Tables cleaned.');
    }

    /**
     * Removes entries from the database which have become irrelevant with time.
     * @return void
     */
    private function cleanDeprecated()
    {
        // Remove units unreferenced by instances.
        /* @var QueryMySQLi $query */
        $query = Database::getQuery();
        $query->delete('units AS u')
            ->leftJoinX('instances AS i', ['i.unitID = u.id'])
            ->where([Database::qn('i.id') . ' IS NULL']);
        Database::setQuery($query);
        Database::execute();

        $currentID = Terms::getCurrentID();
        $termStart = Terms::getStartDate($currentID);

        $query = Database::getQuery();
        $query->selectX('id', 'terms')->where("endDate < '$termStart'");
        Database::setQuery($query);

        // Remove runs, schedules and units (explicitly marked as removed) associated with completed terms.
        if ($termIDs = Database::loadIntColumn()) {
            $query = Database::getQuery();
            $query->deleteX('runs', 'termID', $termIDs);
            Database::setQuery($query);
            Database::execute();

            $query = Database::getQuery();
            $query->deleteX('schedules', 'termID', $termIDs);
            Database::setQuery($query);
            Database::execute();

            $query = Database::getQuery();
            $query->deleteX('units', 'termID', $termIDs)->whereIn('delta', ['removed'], ParameterType::STRING);
            Database::setQuery($query);
            Database::execute();
        }

        $dateCondition = Database::qn('b.date') . " < '$termStart'";

        // Remove instances and instance associations (explicitly marked as removed) from previous terms.
        $query = Database::getQuery();
        $query->delete('instances AS i')
            ->innerJoinX('blocks AS b', ['b.id = i.blockID'])
            ->where([$dateCondition, Database::qn('i.delta') . " = 'removed'"]);
        Database::setQuery($query);
        Database::execute();

        $query = Database::getQuery();
        $query->delete('instance_persons AS ip')
            ->innerJoinX('instances AS i', ['i.id = ip.instanceID'])
            ->innerJoinX('blocks AS b', ['b.id = i.blockID'])
            ->where([$dateCondition, Database::qn('ip.delta') . " = 'removed'"]);
        Database::setQuery($query);
        Database::execute();

        $query = Database::getQuery();
        $query->delete('instance_groups AS ig')
            ->innerJoinX('instance_persons AS ip', ['ip.id = ig.assocID'])
            ->innerJoinX('instances AS i', ['i.id = ip.instanceID'])
            ->innerJoinX('blocks AS b', ['b.id = i.blockID'])
            ->where([$dateCondition, Database::qn('ig.delta') . " = 'removed'"]);
        Database::setQuery($query);
        Database::execute();

        $query = Database::getQuery();
        $query->delete('instance_rooms AS ir')
            ->innerJoinX('instance_persons AS ip', ['ip.id = ir.assocID'])
            ->innerJoinX('instances AS i', ['i.id = ip.instanceID'])
            ->innerJoinX('blocks AS b', ['b.id = i.blockID'])
            ->where([$dateCondition, Database::qn('ir.delta') . " = 'removed'"]);
        Database::setQuery($query);
        Database::execute();

        // Remove blocks unreferenced by instances.
        $query = Database::getQuery();
        $query->delete('blocks AS b')
            ->leftJoinX('instances AS i', ['i.blockID = b.id'])
            ->where([$dateCondition, Database::qn('i.id') . ' IS NULL']);
        Database::setQuery($query);
        Database::execute();

        // Remove events unreferenced by instances.
        $query = Database::getQuery();
        $query->delete('events AS e')
            ->leftJoinX('instances AS i', ['i.eventID = e.id'])
            ->where([Database::qn('i.id') . ' IS NULL']);
        Database::setQuery($query);
        Database::execute();
    }

    /**
     * Removes entries related to people from the database.
     * @return void
     */
    private function cleanPeople()
    {
        $currentID    = Terms::getCurrentID();
        $currentStart = Terms::getStartDate($currentID);
        $byDays       = date('Y-m-d H:i:s', strtotime('-180 days'));
        $byTerm       = date('Y-m-d H:i:s', strtotime($currentStart));
        $cutoff       = min($byDays, $byTerm);

        // Past users that were never participants.
        $query = Database::getQuery();
        $where = [Database::qn('p.id') . ' IS NULL', Database::qn('u.lastvisitDate') . " < '$cutoff'"];
        $query->selectX('u.id', '#__users AS u')->leftJoinX('participants AS p', ['p.id = u.id'])->where($where);
        Database::setQuery($query);

        $this->purgeUsers(Database::loadIntColumn(), 'zombie');

        // Inactive participants with no associations.
        $query = Database::getQuery();
        $where = [
            Database::qn('cp.id') . ' IS NULL',
            Database::qn('ip.id') . ' IS NULL',
            Database::qn('u.lastvisitDate') . " < '$cutoff'"
        ];
        $query->selectX('u.id, u.lastvisitDate', '#__users AS u')
            ->innerJoinX('participants AS p', ['p.id = u.id'])
            ->leftJoinX('course_participants AS cp', ['cp.participantID = p.id'])
            ->leftJoinX('instance_participants AS ip', ['ip.participantID = p.id'])
            ->where($where);
        Database::setQuery($query);

        $this->purgeUsers(Database::loadIntColumn(), 'inactive');

        // Unassociated persons entries
        $query = Database::getQuery();
        $where = [
            Database::qn('ec.id') . ' IS NULL',
            Database::qn('ip.id') . ' IS NULL',
            Database::qn('sp.id') . ' IS NULL'
        ];
        $query->selectX('p.id, p.code, p.forename, p.surname', 'persons AS p')
            ->leftJoinX('event_coordinators AS ec', ['ec.personID = p.id'])
            ->leftJoinX('instance_persons AS ip', ['ip.personID = p.id'])
            ->leftJoinX('subject_persons AS sp', ['sp.personID = p.id'])
            ->where($where);
        Database::setQuery($query);

        $deleted = 0;
        $options = ['text_file' => 'organizer_removed_persons.php', 'text_entry_format' => '{DATETIME}: {MESSAGE}'];
        Log::addLogger($options, Log::ALL, ['com_organizer.cleaning']);
        foreach (Database::loadAssocList() as $person) {
            $message = $person['surname'];
            $message .= !empty($person['forename']) ? ", {$person['forename']}" : '';
            $message .= ": {$person['code']}";
            Log::add($message, Log::DEBUG, 'com_organizer.cleaning');

            $table = new Tables\Persons();
            $table->delete($person['id']);
            $deleted++;
        }

        Application::message("$deleted un-associated person entries deleted.");
    }

    /**
     * Deletes a user contingent. Will not delete if the user is assigned to groups other than registered.
     *
     * @param   int[]  $userIDs  the ids of the users to delete
     *
     * @return void
     */
    private function purgeUsers(array $userIDs, string $adjective)
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

        Application::message("$deleted $adjective users deleted.");
    }

    /**
     * Re-keys a table
     *
     * @param   string  $table  The name of the table to be compacted without the component prefix.
     *
     * @return void
     */
    private function reKeyTable(string $table)
    {
        Database::setQuery('SET @count = 0');
        Database::execute();

        Database::setQuery("UPDATE #__organizer_$table SET id = @count:= @count + 1");
        Database::execute();

        Database::setQuery("ALTER TABLE #__organizer_$table AUTO_INCREMENT = 1");
        Database::execute();
    }

    /**
     * Renumbers the ids of the tables declared compactable.
     * @return void
     */
    public function reKeyTables()
    {
        foreach ($this->compacTables as $table) {
            $this->reKeyTable($table);
        }

        Application::message('Tables re-keyed.');
    }
}
