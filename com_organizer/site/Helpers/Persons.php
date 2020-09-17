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

use Joomla\CMS\Factory;
use Organizer\Tables;

/**
 * Provides general functions for person access checks, data retrieval and display.
 */
class Persons extends Associated implements Selectable
{
	const COORDINATES = 1, NO = 0, TEACHER = 2, YES = 1;

	static protected $resource = 'person';

	/**
	 * Retrieves person entries from the database
	 *
	 * @return string  the persons who hold courses for the selected program and pool
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

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('DISTINCT p.id, p.forename, p.surname')->from('#__organizer_persons AS p');
		$query->innerJoin('#__organizer_subject_persons AS sp ON sp.personID = p.id');
		$query->innerJoin('#__organizer_curricula AS c ON c.subjectID = sp.subjectID');
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

		$query->order('p.surname, p.forename');
		$dbo->setQuery($query);

		$persons = OrganizerHelper::executeQuery('loadObjectList', []);
		if (empty($persons))
		{
			return '[]';
		}

		foreach ($persons as $key => $value)
		{
			$persons[$key]->name = empty($value->forename) ?
				$value->surname : $value->surname . ', ' . $value->forename;
		}

		return json_encode($persons);
	}

	/**
	 * Checks for multiple person entries (roles) for a subject and removes the lesser
	 *
	 * @param   array &$list  the list of persons with a role for the subject
	 *
	 * @return void  removes duplicate list entries dependent on role
	 */
	private static function ensureUnique(&$list)
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
	public static function getDataBySubject($subjectID, $role = null, $multiple = false, $unique = true)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('p.id, p.surname, p.forename, p.title, p.username, u.id AS userID, role, code');
		$query->from('#__organizer_persons AS p');
		$query->innerJoin('#__organizer_subject_persons AS sp ON sp.personID = p.id');
		$query->leftJoin('#__users AS u ON u.username = p.username');
		$query->where("sp.subjectID = '$subjectID' ");

		if (!empty($role))
		{
			$query->where("sp.role = '$role'");
		}

		$query->order('surname ASC');
		$dbo->setQuery($query);

		if ($multiple)
		{
			$personList = OrganizerHelper::executeQuery('loadAssocList', []);
			if (empty($personList))
			{
				return [];
			}

			if ($unique)
			{
				self::ensureUnique($personList);
			}

			return $personList;
		}

		return OrganizerHelper::executeQuery('loadAssoc', []);
	}

	/**
	 * Generates a default person text based upon organizer's internal data
	 *
	 * @param   int  $personID  the person's id
	 *
	 * @return string  the default name of the person
	 */
	public static function getDefaultName($personID)
	{
		$person = new Tables\Persons;
		$person->load($personID);

		$return = '';
		if (!empty($person->id))
		{
			$title    = empty($person->title) ? '' : "{$person->title} ";
			$forename = empty($person->forename) ? '' : "{$person->forename} ";
			$surname  = $person->surname;
			$return   .= $title . $forename . $surname;
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
	public static function getOrganizationNames($personID)
	{
		$dbo   = Factory::getDbo();
		$tag   = Languages::getTag();
		$query = $dbo->getQuery(true);

		$query->select("o.shortName_$tag AS name")
			->from('#__organizer_organizations AS o')
			->innerJoin('#__organizer_associations AS a ON a.organizationID = o.id')
			->where("personID = $personID");
		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadColumn', []);
	}

	/**
	 * Generates a preformatted person text based upon organizer's internal data
	 *
	 * @param   int   $personID  the person's id
	 * @param   bool  $short     Whether or not the person's forename should be abbrevieated
	 *
	 * @return string  the default name of the person
	 */
	public static function getLNFName($personID, $short = false)
	{
		$person = new Tables\Persons;
		$person->load($personID);

		$return = '';
		if (!empty($person->id))
		{
			if (!empty($person->forename))
			{
				// Getting the first letter by other means can cause encoding problems with 'interesting' first names.
				$forename = $short ? mb_substr($person->forename, 0, 1) . '.' : $person->forename;
			}
			$return = $person->surname;
			$return .= empty($forename) ? '' : ", $forename";
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
	public static function getIDByUserID($userID = null)
	{
		if (!$user = Users::getUser($userID))
		{
			return 0;
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('id')
			->from('#__organizer_persons')
			->where("username = '{$user->username}'");
		$dbo->setQuery($query);

		return (int) OrganizerHelper::executeQuery('loadResult', 0);
	}

	/**
	 * Retrieves a list of resources in the form of name => id.
	 *
	 * @return array the resources, or empty
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
	 * Getter method for persons in database. Only retrieving the IDs here allows for formatting the names according to
	 * the needs of the calling views.
	 *
	 * @return array  the scheduled persons which the user has access to
	 */
	public static function getResources()
	{
		$user = Users::getUser();
		if (empty($user->id))
		{
			return [];
		}

		$organizationID  = Input::getInt('organizationID');
		$organizationIDs = $organizationID ? [$organizationID] : Input::getFilterIDs('organization');
		$thisPersonID    = self::getIDByUserID();
		if (empty($organizationIDs) and empty($thisPersonID))
		{
			return [];
		}

		foreach ($organizationIDs as $key => $organizationID)
		{
			if (!Can::view('organization', $organizationID))
			{
				unset($organizationIDs[$key]);
			}
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

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

			if ($selectedCategories = Input::getFilterIDs('category'))
			{
				$categoryIDs = "'" . str_replace(',', "', '", $selectedCategories) . "'";
				$query->innerJoin('#__organizer_instance_persons AS ip ON ip.personID = p.id')
					->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ip.id')
					->innerJoin('#__organizer_groups AS g ON g.id = ig.groupID');

				$where .= " AND g.categoryID in ($categoryIDs)";
				$where = "($where)";
			}

			$wherray[] = $where;

			$query->where(implode(' OR ', $wherray));
		}

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
	}

	/**
	 * Function to sort persons by their surnames and forenames.
	 *
	 * @param   array &$persons  the persons array to sort.
	 */
	public static function nameSort(&$persons)
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
	public static function roleSort(&$persons)
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
