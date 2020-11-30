<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;

use Organizer\Adapters\Database;
use Organizer\Tables;

/**
 * Provides general functions for person access checks, data retrieval and display.
 */
class Persons extends Associated implements Selectable
{
	// TODO move all person related constants here and use this class instead of redefining them
	private const COORDINATES = 1;

	protected static $resource = 'person';

	/**
	 * Retrieves person entries from the database
	 *
	 * @return array  the persons who hold courses for the selected program and pool
	 */
	public static function byProgramOrPool()
	{
		$programID = Input::getInt('programID', -1);
		$poolID    = Input::getInt('poolID', -1);

		if ($poolID > 0)
		{
			$boundarySet = Pools::getRanges($poolID);
		}
		else
		{
			$boundarySet = Programs::getRanges($programID);
		}

		$query = Database::getQuery();
		$query->select('DISTINCT p.id, p.forename, p.surname')
			->from('#__organizer_persons AS p')
			->innerJoin('#__organizer_subject_persons AS sp ON sp.personID = p.id')
			->innerJoin('#__organizer_curricula AS c ON c.subjectID = sp.subjectID')
			->order('p.surname, p.forename');

		if (!empty($boundarySet))
		{
			$where   = '';
			$initial = true;
			foreach ($boundarySet as $boundaries)
			{
				$where   .= $initial ?
					"((c.lft >= '{$boundaries['lft']}' AND c.rgt <= '{$boundaries['rgt']}')"
					: " OR (c.lft >= '{$boundaries['lft']}' AND c.rgt <= '{$boundaries['rgt']}')";
				$initial = false;
			}

			$query->where($where . ')');
		}

		Database::setQuery($query);

		if (!$persons = Database::loadObjectList())
		{
			return [];
		}

		foreach ($persons as $key => $value)
		{
			$persons[$key]->name = empty($value->forename) ? $value->surname : $value->surname . ', ' . $value->forename;
		}

		return $persons;
	}

	/**
	 * Checks for multiple person entries (roles) for a subject and removes the lesser
	 *
	 * @param   array &$list  the list of persons with a role for the subject
	 *
	 * @return void  removes duplicate list entries dependent on role
	 */
	private static function ensureUnique(array &$list)
	{
		$keysToIds = [];
		foreach ($list as $key => $item)
		{
			$keysToIds[$key] = $item['id'];
		}

		$valueCount = array_count_values($keysToIds);
		foreach ($list as $key => $item)
		{
			$unset = ($valueCount[$item['id']] > 1 and $item['role'] > 1);
			if ($unset)
			{
				unset($list[$key]);
			}
		}
	}

	/**
	 * Retrieves the persons associated with a given subject, optionally filtered by role.
	 *
	 * @param   int   $subjectID  the subject's id
	 * @param   int   $role       represents the person's role for the subject
	 * @param   bool  $multiple   whether or not multiple results are desired
	 * @param   bool  $unique     whether or not unique results are desired
	 *
	 * @return array  an array of person data
	 */
	public static function getDataBySubject(int $subjectID, $role = 0, $multiple = false, $unique = true)
	{
		$query = Database::getQuery();
		$query->select('p.id, p.surname, p.forename, p.title, p.username, u.id AS userID, sp.role, code')
			->from('#__organizer_persons AS p')
			->innerJoin('#__organizer_subject_persons AS sp ON sp.personID = p.id')
			->leftJoin('#__users AS u ON u.username = p.username')
			->where("sp.subjectID = $subjectID")
			->order('surname');

		if ($role)
		{
			$query->where("sp.role = $role");
		}

		Database::setQuery($query);

		if ($multiple)
		{
			if (!$personList = Database::loadAssocList())
			{
				return [];
			}

			if ($unique)
			{
				self::ensureUnique($personList);
			}

			return $personList;
		}

		return Database::loadAssoc();
	}

	/**
	 * Generates a default person text based upon organizer's internal data
	 *
	 * @param   int  $personID  the person's id
	 *
	 * @return string  the default name of the person
	 */
	public static function getDefaultName(int $personID)
	{
		$person = new Tables\Persons();
		$person->load($personID);
		$return = '';

		if ($person->id)
		{
			$title    = $person->title ? "{$person->title} " : '';
			$forename = $person->forename ? "{$person->forename} " : '';
			$surname  = $person->surname;
			$return   = $title . $forename . $surname;
		}

		return $return;
	}

	/**
	 * Gets the organizations with which the person is associated
	 *
	 * @param   int  $personID  the person's id
	 *
	 * @return array the organizations with which the person is associated id => name
	 */
	public static function getOrganizationNames(int $personID)
	{
		$query = Database::getQuery();
		$tag   = Languages::getTag();
		$query->select("o.shortName_$tag AS name")
			->from('#__organizer_organizations AS o')
			->innerJoin('#__organizer_associations AS a ON a.organizationID = o.id')
			->where("personID = $personID");
		Database::setQuery($query);

		return Database::loadColumn();
	}

	/**
	 * Generates a preformatted person text based upon organizer's internal data
	 *
	 * @param   int   $personID  the person's id
	 * @param   bool  $short     Whether or not the person's forename should be abbreviated
	 *
	 * @return string  the default name of the person
	 */
	public static function getLNFName(int $personID, bool $short = false)
	{
		$person = new Tables\Persons();
		$person->load($personID);
		$return = '';

		if ($person->id)
		{
			$return = $person->surname;

			if ($person->forename)
			{
				// Getting the first letter by other means can cause encoding problems with 'interesting' first names.
				$forename = $short ? mb_substr($person->forename, 0, 1) . '.' : $person->forename;
				$return   .= empty($forename) ? '' : ", $forename";
			}
		}

		return $return;
	}

	/**
	 * Checks whether the user has an associated person resource by their username, returning the id of the person
	 * entry if existent.
	 *
	 * @param   int  $userID  the user id if empty the current user is used
	 *
	 * @return int the id of the person entry if existent, otherwise 0
	 */
	public static function getIDByUserID(int $userID = 0)
	{
		if (!$user = Users::getUser($userID))
		{
			return 0;
		}

		$query = Database::getQuery();
		$query->select('id')
			->from('#__organizer_persons')
			->where("username = '{$user->username}'");
		Database::setQuery($query);

		return Database::loadInt();
	}

	/**
	 * @inheritDoc
	 */
	public static function getOptions()
	{
		$options = [];
		foreach (self::getResources() as $person)
		{
			if ($person['active'])
			{
				$name     = $person['surname'];
				$forename = trim($person['forename']);
				$name     .= $forename ? ", $forename" : '';

				$options[] = HTML::_('select.option', $person['id'], $name);
			}
		}

		return $options;
	}

	/**
	 * @inheritDoc
	 */
	public static function getResources()
	{
		$user = Users::getUser();

		if (!$user->id)
		{
			return [];
		}

		// TODO Remove departmentIDs on completion of migration.
		$organizationID  = Input::getInt('organizationID', Input::getInt('departmentIDs'));
		$organizationIDs = $organizationID ? [$organizationID] : Input::getFilterIDs('organization');
		$thisPersonID    = self::getIDByUserID();

		foreach ($organizationIDs as $key => $organizationID)
		{
			if (!Can::view('organization', $organizationID))
			{
				unset($organizationIDs[$key]);
			}
		}

		if (empty($organizationIDs) and empty($thisPersonID))
		{
			return [];
		}

		$query = Database::getQuery();

		$query->select('DISTINCT p.*')
			->from('#__organizer_persons AS p')
			->where('p.active = 1')
			->order('p.surname, p.forename');

		$wherray = [];
		if ($thisPersonID)
		{
			$wherray[] = "p.username = '$user->username'";
		}

		if (count($organizationIDs))
		{
			$query->innerJoin('#__organizer_associations AS a ON a.personID = p.id');

			$where = 'a.organizationID IN (' . implode(',', $organizationIDs) . ')';

			// TODO Remove programIDs on completion of migration.
			if ($categoryID = Input::getInt('programIDs') or $categoryID = Input::getInt('categoryID'))
			{
				$categoryIDs = [$categoryID];
			}

			$categoryIDs = empty($categoryIDs) ? Input::getIntCollection('categoryIDs') : $categoryIDs;
			$categoryIDs = empty($categoryIDs) ? Input::getFilterIDs('category') : $categoryIDs;

			if ($categoryIDs and $categoryIDs = implode(',', $categoryIDs))
			{
				$query->innerJoin('#__organizer_instance_persons AS ip ON ip.personID = p.id')
					->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ip.id')
					->innerJoin('#__organizer_groups AS g ON g.id = ig.groupID');

				$where .= " AND g.categoryID in ($categoryIDs)";
				$where = "($where)";
			}

			$wherray[] = $where;
		}

		if ($wherray)
		{
			$query->where(implode(' OR ', $wherray));
		}

		Database::setQuery($query);

		return Database::loadAssocList();
	}

	/**
	 * Function to sort persons by their surnames and forenames.
	 *
	 * @param   array &$persons  the persons array to sort.
	 */
	public static function nameSort(array &$persons)
	{
		uasort($persons, function ($personOne, $personTwo) {
			if ($personOne['surname'] == $personTwo['surname'])
			{
				return $personOne['forename'] > $personTwo['forename'];
			}

			return $personOne['surname'] > $personTwo['surname'];
		});
	}

	/**
	 * Function to sort persons by their roles.
	 *
	 * @param   array &$persons  the persons array to sort.
	 */
	public static function roleSort(array &$persons)
	{
		uasort($persons, function ($personOne, $personTwo) {
			$roleOne = isset($personOne['role'][self::COORDINATES]);
			$roleTwo = isset($personTwo['role'][self::COORDINATES]);
			if ($roleOne or !$roleTwo)
			{
				return 1;
			}

			return -1;
		});
	}
}
