<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Joomla\CMS\Factory;
use Organizer\Adapters\Database;
use Organizer\Adapters\Queries\QueryMySQLi;
use Organizer\Helpers\OrganizerHelper;
use Organizer\Helpers\Terms;

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
	 *
	 * @todo add flooring management
	 * @todo remove flooring insert values
	 * @todo dynamically add flooring types during room import process
	 *
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
	 *
	 * @return void
	 */
	private function cleanAssociations()
	{
		$dbo        = Factory::getDbo();
		$select     = ['duplicate.id'];
		$from       = ['associations AS duplicate', 'associations AS reference'];
		$conditions = [
			$dbo->quoteName('duplicate.id') . ' > ' . $dbo->quoteName('reference.id'),
			$dbo->quoteName('duplicate.organizationID') . ' = ' . $dbo->quoteName('reference.organizationID')
		];

		$orConditions   = [];
		$orConditions[] = [
			$dbo->quoteName('duplicate.categoryID') . ' = ' . $dbo->quoteName('reference.categoryID'),
			$dbo->quoteName('duplicate.categoryID') . ' IS NOT NULL'
		];
		$orConditions[] = [
			$dbo->quoteName('duplicate.groupID') . ' = ' . $dbo->quoteName('reference.groupID'),
			$dbo->quoteName('duplicate.groupID') . ' IS NOT NULL'
		];
		$orConditions[] = [
			$dbo->quoteName('duplicate.personID') . ' = ' . $dbo->quoteName('reference.personID'),
			$dbo->quoteName('duplicate.personID') . ' IS NOT NULL'
		];
		$orConditions[] = [
			$dbo->quoteName('duplicate.poolID') . ' = ' . $dbo->quoteName('reference.poolID'),
			$dbo->quoteName('duplicate.poolID') . ' IS NOT NULL'
		];
		$orConditions[] = [
			$dbo->quoteName('duplicate.programID') . ' = ' . $dbo->quoteName('reference.programID'),
			$dbo->quoteName('duplicate.programID') . ' IS NOT NULL'
		];
		$orConditions[] = [
			$dbo->quoteName('duplicate.subjectID') . ' = ' . $dbo->quoteName('reference.subjectID'),
			$dbo->quoteName('duplicate.subjectID') . ' IS NOT NULL'
		];
		foreach ($orConditions as &$andConditions)
		{
			$andConditions = implode(' AND ', $andConditions);
		}
		$conditions[] = '((' . implode(') OR (', $orConditions) . '))';


		/* @var QueryMySQLi $query */
		$query = Database::getQuery();
		$query->selectX($select, $from)
			->where($conditions);
		Database::setQuery($query);;

		if ($duplicateIDs = Database::loadIntColumn())
		{
			/* @var QueryMySQLi $query */
			$query = Database::getQuery();
			$query->deleteX('associations', 'id', $duplicateIDs);
			Database::setQuery($query);
			Database::execute();
		}
	}

	/**
	 * Executes functions to clean database tables.
	 *
	 * @return void
	 */
	public function cleanDB()
	{
		$this->cleanAssociations();
		$this->cleanDeprecated();

		OrganizerHelper::message('Tables cleaned.');
	}

	/**
	 * Removes entries from the database which have become irrelevant with time.
	 *
	 * @return void
	 */
	private function cleanDeprecated()
	{
		$dbo = Factory::getDbo();

		// Remove units unreferenced by instances.
		/* @var QueryMySQLi $query */
		$query = Database::getQuery();
		$query->delete('units AS u')
			->leftJoinX('instances AS i', ['i.unitID = u.id'])
			->where([$dbo->quoteName('i.id') . ' IS NULL']);
		Database::setQuery($query);
		Database::execute();

		$currentID = Terms::getCurrentID();
		$termStart = Terms::getStartDate($currentID);

		/* @var QueryMySQLi $query */
		$query = Database::getQuery();
		$query->selectX('id', 'terms')->where("endDate < '$termStart'");
		Database::setQuery($query);

		// Remove runs, schedules and units (explicitly marked as removed) associated with completed terms.
		if ($termIDs = Database::loadIntColumn())
		{
			/* @var QueryMySQLi $query */
			$query = Database::getQuery();
			$query->deleteX('runs', 'termID', $termIDs);
			Database::setQuery($query);
			Database::execute();

			/* @var QueryMySQLi $query */
			$query = Database::getQuery();
			$query->deleteX('schedules', 'termID', $termIDs);
			Database::setQuery($query);
			Database::execute();

			/* @var QueryMySQLi $query */
			$query = Database::getQuery();
			$query->deleteX('units', 'termID', $termIDs)->wherein('delta', ['removed'], false, true);
			Database::setQuery($query);
			Database::execute();
		}

		$dateCondition = $dbo->quoteName('b.date') . " < '$termStart'";

		// Remove instances and instance associations (explicitly marked as removed) from previous terms.
		/* @var QueryMySQLi $query */
		$query = Database::getQuery();
		$query->delete('instances AS i')
			->innerJoinX('blocks AS b', ['b.id = i.blockID'])
			->where([$dateCondition, $dbo->quoteName('i.delta') . " = 'removed'"]);
		Database::setQuery($query);
		Database::execute();

		/* @var QueryMySQLi $query */
		$query = Database::getQuery();
		$query->delete('instance_persons AS ip')
			->innerJoinX('instances AS i', ['i.id = ip.instanceID'])
			->innerJoinX('blocks AS b', ['b.id = i.blockID'])
			->where([$dateCondition, $dbo->quoteName('ip.delta') . " = 'removed'"]);
		Database::setQuery($query);
		Database::execute();

		/* @var QueryMySQLi $query */
		$query = Database::getQuery();
		$query->delete('instance_groups AS ig')
			->innerJoinX('instance_persons AS ip', ['ip.id = ig.assocID'])
			->innerJoinX('instances AS i', ['i.id = ip.instanceID'])
			->innerJoinX('blocks AS b', ['b.id = i.blockID'])
			->where([$dateCondition, $dbo->quoteName('ig.delta') . " = 'removed'"]);
		Database::setQuery($query);
		Database::execute();

		/* @var QueryMySQLi $query */
		$query = Database::getQuery();
		$query->delete('instance_rooms AS ir')
			->innerJoinX('instance_persons AS ip', ['ip.id = ir.assocID'])
			->innerJoinX('instances AS i', ['i.id = ip.instanceID'])
			->innerJoinX('blocks AS b', ['b.id = i.blockID'])
			->where([$dateCondition, $dbo->quoteName('ir.delta') . " = 'removed'"]);
		Database::setQuery($query);
		Database::execute();

		// Remove blocks unreferenced by instances.
		/* @var QueryMySQLi $query */
		$query = Database::getQuery();
		$query->delete('blocks AS b')
			->leftJoinX('instances AS i', ['i.blockID = b.id'])
			->where([$dateCondition, $dbo->quoteName('i.id') . ' IS NULL']);
		Database::setQuery($query);
		Database::execute();

		// Remove events unreferenced by instances.
		/* @var QueryMySQLi $query */
		$query = Database::getQuery();
		$query->delete('events AS e')
			->leftJoinX('instances AS i', ['i.eventID = e.id'])
			->where([$dbo->quoteName('i.id') . ' IS NULL']);
		Database::setQuery($query);
		Database::execute();
	}

	/**
	 * Rekeys a table
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
		foreach ($this->compacTables as $table)
		{
			$this->reKeyTable($table);
		}

		OrganizerHelper::message('Tables re-keyed.');
	}
}
