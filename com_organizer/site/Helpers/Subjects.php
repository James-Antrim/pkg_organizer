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
 * Provides general functions for subject access checks, data retrieval and display.
 */
class Subjects extends Curricula implements Selectable
{
	/**
	 * Check if user one of the subject's coordinators.
	 *
	 * @param   int  $subjectID  the optional id of the subject
	 * @param   int  $personID   the optional id of the person entry
	 *
	 * @return boolean true if the user is a coordinator, otherwise false
	 */
	public static function coordinates($subjectID = 0, $personID = 0)
	{
		if (!$personID)
		{
			$user     = Factory::getUser();
			$personID = Persons::getIDByUserID($user->id);
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('COUNT(*)')
			->from('#__organizer_subject_persons')
			->where("personID = $personID")
			->where("role = 1");

		if ($subjectID)
		{
			$query->where("subjectID = '$subjectID'");
		}

		$dbo->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('loadResult');
	}

	/**
	 * Retrieves the left and right boundaries of the nested program or pool
	 *
	 * @return array
	 */
	private static function getFilterRanges()
	{
		if (!$programBoundaries = Programs::getRanges(Input::getInt('programID')))
		{
			return [];
		}

		if ($poolBoundaries = Pools::getRanges(Input::getInt('poolID'))
			and self::poolInProgram($poolBoundaries, $programBoundaries))
		{
			return $poolBoundaries;
		}

		return $programBoundaries;
	}

	/**
	 * Retrieves the subject name
	 *
	 * @param   int      $subjectID  the table id for the subject
	 * @param   boolean  $withNumber
	 *
	 * @return string the subject name
	 */
	public static function getName($subjectID = 0, $withNumber = false)
	{
		$subjectID = $subjectID ? $subjectID : Input::getID();

		$dbo = Factory::getDbo();
		$tag = Languages::getTag();

		$query = $dbo->getQuery(true);
		$query->select("name_$tag as name")
			->select("shortName_$tag as shortName, abbreviation_$tag as abbreviation, code AS subjectNo")
			->from('#__organizer_subjects')
			->where("id = '$subjectID'");

		$dbo->setQuery($query);

		$names = OrganizerHelper::executeQuery('loadAssoc', []);
		if (empty($names))
		{
			return '';
		}

		$suffix = '';

		if ($withNumber and !empty($names['subjectNo']))
		{
			$suffix .= " ({$names['subjectNo']})";
		}

		if (!empty($names['name']))
		{
			return $names['name'] . $suffix;
		}

		if (!empty($names['shortName']))
		{
			return $names['shortName'] . $suffix;
		}

		return $names['abbreviation'] . $suffix;
	}

	/**
	 * Retrieves the selectable options for the resource.
	 *
	 * @return array the available options
	 */
	public static function getOptions()
	{
		$options = [];
		foreach (self::getResources() as $subject)
		{
			$options[] = HTML::_('select.option', $subject['id'], $subject['name']);
		}

		return $options;
	}

	/**
	 * Retrieves the persons associated with a given subject and their respective roles for it.
	 *
	 * @param   int  $subjectID  the id of the subject with which the persons must be associated
	 * @param   int  $role       the role to be filtered against default none
	 *
	 * @return array the persons associated with the subject, empty if none were found.
	 */
	public static function getPersons($subjectID, $role = null)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('t.id, t.surname, t.forename, t.fieldID, t.title, sp.role')
			->from('#__organizer_persons AS t')
			->innerJoin('#__organizer_subject_persons AS sp ON sp.personID = t.id')
			->where("sp.subjectID = '$subjectID'");

		if (!empty($role) and is_numeric($role))
		{
			$query->where("sp.role = $role");
		}
		$dbo->setQuery($query);

		$results = OrganizerHelper::executeQuery('loadAssocList');
		if (empty($results))
		{
			return [];
		}

		$persons = [];
		foreach ($results as $person)
		{
			$forename = empty($person['forename']) ? '' : $person['forename'];
			$fullName = $person['surname'];
			$fullName .= empty($forename) ? '' : ", {$person['forename']}";
			if (empty($persons[$person['id']]))
			{
				$person['forename'] = $forename;
				$person['title']    = empty($person['title']) ? '' : $person['title'];
				$person['role']     = [$person['role'] => $person['role']];
				$persons[$fullName] = $person;
				continue;
			}

			$persons[$person['id']]['role'] = [$person['role'] => $person['role']];
		}

		Persons::roleSort($persons);
		Persons::nameSort($persons);

		return $persons;
	}

	/**
	 * Looks up the names of the pools associated with the subject
	 *
	 * @param   int  $subjectID  the id of the (plan) subject
	 *
	 * @return array the associated program names
	 */
	public static function getPools($subjectID)
	{
		return Pools::getRanges(self::getRanges($subjectID));
	}

	/**
	 * Gets the mapped curricula ranges for the given resource
	 *
	 * @param   mixed  $subjectID  int resourceID
	 *
	 * @return array the resource ranges
	 */
	public static function getRanges($subjectID)
	{
		if (!is_numeric($subjectID))
		{
			return [];
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('DISTINCT *')
			->from('#__organizer_curricula')
			->where("subjectID = $subjectID")
			->order('lft');
		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
	}

	/**
	 * Gets an array modelling the attributes of the resource.
	 *
	 * @param $resourceID
	 *
	 * @return array
	 */
	public static function getResource($resourceID)
	{
		$table  = new Tables\Subjects;
		$exists = $table->load($resourceID);

		if (!$exists)
		{
			return [];
		}

		$tag     = Languages::getTag();
		$subject = [
			'abbreviation' => $table->{"abbreviation_$tag"},
			'bgColor'      => Fields::getColor($table->fieldID),
			'creditpoints' => $table->creditpoints,
			'field'        => Fields::getName($table->fieldID, 'field'),
			'fieldID'      => $table->fieldID,
			'id'           => $table->id,
			'moduleNo'     => $table->code,
			'name'         => $table->{"name_$tag"},
			'shortName'    => $table->{"shortName_$tag"},
		];

		return $subject;
	}

	/**
	 * Retrieves the resource items.
	 *
	 * @return array the available resources
	 */
	public static function getResources()
	{
		$programID = Input::getInt('programID', -1);
		$personID  = Input::getInt('personID', -1);
		if ($programID === -1 and $personID === -1)
		{
			return [];
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$tag = Languages::getTag();
		$query->select("DISTINCT s.id, s.name_$tag AS name, s.code, s.creditpoints")
			->select('t.surname, t.forename, t.title, t.username')
			->from('#__organizer_subjects AS s')
			->order('name')
			->group('s.id');

		if ($ranges = self::getFilterRanges())
		{
			$query->innerJoin('#__organizer_curricula AS c ON c.subjectID = s.id');
			$wherray = [];

			foreach ($ranges as $boundaries)
			{
				$wherray[] = "(m.lft >= '{$boundaries['lft']}' AND m.rgt <= '{$boundaries['rgt']}')";
			}

			$query->where('(' . implode(' OR ', $wherray) . ')');
		}

		if ($personID !== self::ALL)
		{
			$query->innerJoin('#__organizer_subject_persons AS sp ON sp.subjectID = s.id');
			$query->innerJoin('#__organizer_persons AS t ON t.id = sp.personID');
			$query->where("sp.personID = '$personID'");
		}
		else
		{
			$query->leftJoin('#__organizer_subject_persons AS sp ON sp.subjectID = s.id');
			$query->innerJoin('#__organizer_persons AS t ON t.id = sp.personID');
			$query->where("sp.role = '1'");
		}

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
	}

	/**
	 * Checks whether the pool is subordinate to the selected program
	 *
	 * @param   array  $poolBoundaries     the pool's left and right values
	 * @param   array  $programBoundaries  the program's left and right values
	 *
	 * @return boolean  true if the pool is subordinate to the program,
	 *                   otherwise false
	 */
	private static function poolInProgram($poolBoundaries, $programBoundaries)
	{
		$first = $poolBoundaries[0];
		$last  = end($poolBoundaries);

		$leftValid  = $first['lft'] > $programBoundaries[0]['lft'];
		$rightValid = $last['rgt'] < $programBoundaries[0]['rgt'];
		if ($leftValid and $rightValid)
		{
			return true;
		}

		return false;
	}

	/**
	 * Check if the user is one of the subject's teachers.
	 *
	 * @param   int  $subjectID  the optional id of the subject
	 * @param   int  $personID   the optional id of the person entry
	 *
	 * @return boolean true if the user a teacher for the subject, otherwise false
	 */
	public static function teaches($subjectID = 0, $personID = 0)
	{
		if (!$personID)
		{
			$user     = Factory::getUser();
			$personID = Persons::getIDByUserID($user->id);
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('COUNT(*)')
			->from('#__organizer_subject_persons')
			->where("personID = $personID")
			->where("role = 2");

		if ($subjectID)
		{
			$query->where("subjectID = '$subjectID'");
		}

		$dbo->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('loadResult');
	}
}
