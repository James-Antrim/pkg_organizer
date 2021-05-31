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
 * Provides general functions for subject access checks, data retrieval and display.
 */
class Subjects extends Curricula
{
	protected static $resource = 'subject';

	/**
	 * Check if user one of the subject's coordinators.
	 *
	 * @param   int  $subjectID  the optional id of the subject
	 * @param   int  $personID   the optional id of the person entry
	 *
	 * @return bool true if the user is a coordinator, otherwise false
	 */
	public static function coordinates(int $subjectID = 0, int $personID = 0): bool
	{
		$personID = $personID ?: Persons::getIDByUserID(Users::getID());
		$query    = Database::getQuery(true);
		$query->select('COUNT(*)')
			->from('#__organizer_subject_persons')
			->where("personID = $personID")
			->where("role = 1");

		if ($subjectID)
		{
			$query->where("subjectID = '$subjectID'");
		}

		Database::setQuery($query);

		return Database::loadBool();
	}

	/**
	 * Retrieves the left and right boundaries of the nested program or pool
	 *
	 * @return array
	 */
	private static function getFilterRanges(): array
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
	 * @param   int   $resourceID  the table id for the subject
	 * @param   bool  $withNumber  whether to integrate the subject code directly into the name
	 *
	 * @return string the subject name
	 */
	public static function getName(int $resourceID = 0, bool $withNumber = false): string
	{
		$query      = Database::getQuery(true);
		$resourceID = $resourceID ?: Input::getID();
		$tag        = Languages::getTag();
		$query->select("fullName_$tag as name, abbreviation_$tag as abbreviation")
			->select("code AS subjectNo")
			->from('#__organizer_subjects')
			->where("id = $resourceID");
		Database::setQuery($query);

		if (!$names = Database::loadAssoc())
		{
			return '';
		}

		$suffix = '';

		if ($withNumber and !empty($names['subjectNo']))
		{
			$suffix .= " ({$names['subjectNo']})";
		}

		if ($names['name'])
		{
			return $names['name'] . $suffix;
		}

		return $names['abbreviation'] . $suffix;
	}

	/**
	 * @inheritDoc
	 */
	public static function getOptions(): array
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
	public static function getPersons(int $subjectID, int $role = 0): array
	{
		$query = Database::getQuery(true);
		$query->select('p.id, p.surname, p.forename, p.title, sp.role')
			->from('#__organizer_persons AS p')
			->innerJoin('#__organizer_subject_persons AS sp ON sp.personID = p.id')
			->where("sp.subjectID = $subjectID");

		if ($role)
		{
			$query->where("sp.role = $role");
		}

		Database::setQuery($query);

		if (!$results = Database::loadAssocList())
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
	public static function getPools(int $subjectID): array
	{
		return Pools::getRanges(self::getRanges($subjectID));
	}

	/**
	 * Retrieves the ids of subjects registered as prerequisites for a given subject
	 *
	 * @param   int  $subjectID  the id of the subject
	 *
	 *
	 * @return array the associated prerequisites
	 */
	public static function getPostrequisites(int $subjectID): array
	{
		return self::getRequisites($subjectID, 'post');
	}

	/**
	 * Retrieves the ids of subjects registered as prerequisites for a given subject
	 *
	 * @param   int  $subjectID  the id of the subject
	 *
	 * @return array the associated prerequisites
	 */
	public static function getPrerequisites(int $subjectID): array
	{
		return self::getRequisites($subjectID, 'pre');
	}

	/**
	 * @inheritDoc
	 */
	public static function getRanges($identifiers): array
	{
		if (!$identifiers or !is_numeric($identifiers))
		{
			return [];
		}

		$query = Database::getQuery(true);
		$query->select('DISTINCT *')
			->from('#__organizer_curricula')
			->where("subjectID = $identifiers")
			->order('lft');
		Database::setQuery($query);

		return Database::loadAssocList();
	}

	/**
	 * Retrieves the ids of subjects registered as prerequisites for a given subject
	 *
	 * @param   int     $subjectID  the id of the subject
	 * @param   string  $direction  pre|post the direction of the subject dependency
	 *
	 * @return array the associated prerequisites
	 */
	private static function getRequisites(int $subjectID, string $direction): array
	{
		if ($direction === 'pre')
		{
			$fromColumn = 'subjectID';
			$toColumn   = 'prerequisiteID';
		}
		else
		{
			$fromColumn = 'prerequisiteID';
			$toColumn   = 'subjectID';
		}

		$query = Database::getQuery(true);
		$query->select('DISTINCT target.subjectID')
			->from('#__organizer_curricula AS target')
			->innerJoin("#__organizer_prerequisites AS p ON p.$toColumn = target.id")
			->innerJoin("#__organizer_curricula AS source ON source.id = p.$fromColumn")
			->where("source.subjectID = $subjectID");
		Database::setQuery($query);

		return Database::loadIntColumn();
	}

	/**
	 * Gets an array modeling the attributes of the resource.
	 *
	 * @param   int  $subjectID  the id of the subject
	 *
	 * @return array
	 */
	public static function getSubject(int $subjectID): array
	{
		$table = new Tables\Subjects();

		if (!$table->load($subjectID))
		{
			return [];
		}

		$fieldID         = $table->fieldID ?: 0;
		$organizationIDs = self::getOrganizationIDs($table->id);
		$organizationID  = $organizationIDs ? (int) $organizationIDs[0] : 0;
		$tag             = Languages::getTag();

		return [
			'abbreviation' => $table->{"abbreviation_$tag"},
			'bgColor'      => Fields::getColor($fieldID, $organizationID),
			'creditPoints' => $table->creditPoints,
			'field'        => $fieldID ? Fields::getName($fieldID) : '',
			'fieldID'      => $table->fieldID,
			'id'           => $table->id,
			'moduleNo'     => $table->code,
			'name'         => $table->{"fullName_$tag"}
		];
	}

	/**
	 * @inheritDoc
	 */
	public static function getResources(): array
	{
		$poolID    = Input::getInt('poolID', -1);
		$programID = Input::getInt('programID', -1);
		$personID  = Input::getInt('personID', -1);
		if ($poolID === -1 and $programID === -1 and $personID === -1)
		{
			return [];
		}

		$query = Database::getQuery();
		$tag   = Languages::getTag();
		$query->select("DISTINCT s.id, s.name_$tag AS name, s.code, s.creditPoints")
			->select('p.surname, p.forename, p.title, p.username')
			->from('#__organizer_subjects AS s')
			// sp added later
			->innerJoin('#__organizer_persons AS p ON p.id = sp.personID')
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
			$query->innerJoin('#__organizer_subject_persons AS sp ON sp.subjectID = s.id')->where("sp.personID = $personID");
		}
		else
		{
			$query->leftJoin('#__organizer_subject_persons AS sp ON sp.subjectID = s.id')->where("sp.role = '1'");
		}

		Database::setQuery($query);

		return Database::loadAssocList('id');
	}

	/**
	 * Checks whether the pool is subordinate to the selected program
	 *
	 * @param   array  $poolBoundaries     the pool's left and right values
	 * @param   array  $programBoundaries  the program's left and right values
	 *
	 * @return bool  true if the pool is subordinate to the program,
	 *                   otherwise false
	 */
	public static function poolInProgram(array $poolBoundaries, array $programBoundaries): bool
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
	 * @return bool true if the user a teacher for the subject, otherwise false
	 */
	public static function teaches(int $subjectID = 0, int $personID = 0): bool
	{
		$personID = $personID ?: Persons::getIDByUserID(Users::getID());
		$query    = Database::getQuery();
		$query->select('COUNT(*)')
			->from('#__organizer_subject_persons')
			->where("personID = $personID")
			->where("role = 2");

		if ($subjectID)
		{
			$query->where("subjectID = '$subjectID'");
		}

		Database::setQuery($query);

		return Database::loadBool();
	}
}
