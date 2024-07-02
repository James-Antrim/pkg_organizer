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
use THM\Organizer\Adapters\{Application, Database};
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
    private array $compacTables = [
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
     * Executes functions to clean database tables.
     * @return void
     */
    public function cleanDB(): void
    {
        $this->cleanPeople();

        Application::message('Tables cleaned.');
    }

    /**
     * Removes entries related to people from the database.
     * @return void
     */
    private function cleanPeople(): void
    {
        $currentID    = Terms::currentID();
        $currentStart = Terms::startDate($currentID);
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
     * Re-keys a table
     *
     * @param   string  $table  The name of the table to be compacted without the component prefix.
     *
     * @return void
     */
    private function reKeyTable(string $table): void
    {
        Database::setQuery('SET @count = 0');
        Database::execute();

        Database::setQuery("UPDATE #__organizer_$table SET id = @count:= @count + 1");
        Database::execute();

        Database::setQuery("ALTER TABLE #__organizer_$table AUTO_INCREMENT = 1");
        Database::execute();
    }

    /**
     * Renumbers the ids of the tables declared compactible.
     * @return void
     */
    public function reKeyTables(): void
    {
        foreach ($this->compacTables as $table) {
            $this->reKeyTable($table);
        }

        Application::message('Tables re-keyed.');
    }
}
