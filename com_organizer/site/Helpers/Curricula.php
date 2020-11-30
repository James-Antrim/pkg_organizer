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

use JDatabaseQuery;
use Organizer\Adapters\Database;

abstract class Curricula extends Associated implements Selectable
{
	protected const ALL = '-1', NONE = -1;

	/**
	 * Adds clauses to an array to find subordinate resources in an error state disassociated from a superordinate
	 * resource type.
	 *
	 * @param   JDatabaseQuery  $query   the query to modify
	 * @param   array           $ranges  the ranges of the possible superordinate resources
	 * @param   string          $alias   the alias to use in the query
	 *
	 * @return void modifies the query
	 */
	protected static function filterDisassociated(JDatabaseQuery $query, array $ranges, string $alias)
	{
		$erray = [];

		foreach ($ranges as $range)
		{
			$erray[] = "( $alias.lft NOT BETWEEN '{$range['lft']}' AND '{$range['rgt']}' )";
			$erray[] = "( $alias.rgt NOT BETWEEN '{$range['lft']}' AND '{$range['rgt']}' )";
		}

		$errorClauses = implode(' AND ', $erray);
		$query->where("( ($errorClauses) OR $alias.id IS NULL ) ");
	}

	/**
	 * Filters the curricula ids from an array of ranges.
	 *
	 * @param   array  $ranges  the ranges to filter
	 *
	 * @return array the curricular ids contained in the ranges
	 */
	public static function filterIDs(array $ranges)
	{
		$ids = [];
		foreach ($ranges as $range)
		{
			if (empty($range['id']))
			{
				$ids[] = $range['curriculumID'];
			}
			else
			{
				$ids[] = $range['id'];
			}
		}

		return $ids;
	}

	/**
	 * Filters the curricula ids from an array of ranges.
	 *
	 * @param   array  $ranges  the ranges to filter
	 *
	 * @return array the curricular ids contained in the ranges
	 */
	protected static function filterParentIDs(array $ranges)
	{
		$ids = [];
		foreach ($ranges as $range)
		{
			$ids[] = $range['parentID'];
		}

		return $ids;
	}

	/**
	 * Adds range restrictions for subordinate resources.
	 *
	 * @param   JDatabaseQuery  $query      the query to modify
	 * @param   array           $ranges     the ranges of subordinate resources
	 * @param   int             $subjectID  the id of a specific subject resource to find in context
	 *
	 * @return void modifies the query
	 */
	protected static function filterSubOrdinate(JDatabaseQuery $query, array $ranges, $subjectID = 0)
	{
		$wherray = [];
		foreach ($ranges as $range)
		{
			$wherray[] = "( lft > '{$range['lft']}' AND rgt < '{$range['rgt']}')";
		}

		if ($wherray)
		{
			$query->where('(' . implode(' OR ', $wherray) . ')');
		}

		if ($subjectID)
		{
			$query->where("subjectID = $subjectID");
		}
		else
		{
			$query->where("subjectID IS NOT NULL");
		}
	}

	/**
	 * Adds range restrictions for subordinate resources.
	 *
	 * @param   JDatabaseQuery  $query   the query to modify
	 * @param   array           $ranges  the ranges of subordinate resources
	 *
	 * @return void modifies the query
	 */
	protected static function filterSuperOrdinate(JDatabaseQuery $query, array $ranges)
	{
		$wherray = [];
		foreach ($ranges as $range)
		{
			$wherray[] = "( lft < '{$range['lft']}' AND rgt > '{$range['rgt']}')";
		}

		$query->where('(' . implode(' OR ', $wherray) . ')');
	}

	/**
	 * Recursively builds the curriculum hierarchy inclusive data for resources subordinate to a given range.
	 *
	 * @param   array  $curriculum  the range used as the start point
	 *
	 * @return void modifies the curriculum array
	 */
	public static function getCurriculum(array &$curriculum)
	{
		$invalidRange = (empty($curriculum['lft']) or empty($curriculum['rgt']) or $curriculum['subjectID']);
		if ($invalidRange)
		{
			$curriculum['curriculum'] = [];

			return;
		}

		$query = Database::getQuery();
		$query->select('*')
			->from('#__organizer_curricula')
			->where("lft > {$curriculum['lft']}")
			->where("rgt < {$curriculum['rgt']}")
			->where("level = {$curriculum['level']} + 1")
			->order('ordering');

		// Only pools should be direct subordinates of programs
		if ($curriculum['programID'])
		{
			$query->where("poolID IS NOT NULL");
		}

		Database::setQuery($query);

		if (!$subOrdinates = Database::loadAssocList('id'))
		{
			$curriculum['curriculum'] = [];

			return;
		}

		// Fill data for subordinate resources
		foreach ($subOrdinates as &$subOrdinate)
		{
			$resourceData = $subOrdinate['poolID'] ?
				Pools::getPool($subOrdinate['poolID']) : Subjects::getSubject($subOrdinate['subjectID']);

			// Avoid conflicts between the resource's actual id and the curricula table id
			unset($resourceData['id']);

			$subOrdinate = array_merge($subOrdinate, $resourceData);
			if ($subOrdinate['poolID'])
			{
				self::getCurriculum($subOrdinate);
			}
		}

		$curriculum['curriculum'] = $subOrdinates;
	}

	/**
	 * Retrieves all curriculum ranges subordinate to a program
	 *
	 * @param   array  $programRanges  the ranges of superordinate programs
	 *
	 * @return array  an array containing all ranges subordinate to the ranges specified
	 */
	private static function getMappableRanges(array $programRanges)
	{
		$query = Database::getQuery();
		$query->select('*');
		$query->from('#__organizer_curricula');

		$items = [];
		foreach ($programRanges as $range)
		{
			$query->clear('where');
			$query->where("lft >= {$range['lft']}")
				->where("rgt <= {$range['rgt']}")
				->where('subjectID IS NULL')
				->order('lft ASC');
			Database::setQuery($query);

			if (!$results = Database::loadAssocList())
			{
				continue;
			}

			$items = array_merge($items, $results);
		}

		return $items;
	}

	/**
	 * Retrieves the range for a given id.
	 *
	 * @param   int  $rangeID  the id of the range requested
	 *
	 * @return array  curriculum range
	 */
	public static function getRange(int $rangeID)
	{
		$query = Database::getQuery();
		$query->select('*')->from('#__organizer_curricula')->where("id = $rangeID");
		Database::setQuery($query);

		return Database::loadAssoc();
	}

	/**
	 * Gets the mapped curricula ranges for the given resource
	 *
	 * @param   int  $resourceID  the resource ID
	 *
	 * @return array the resource ranges
	 */
	public static function getRangeIDs(int $resourceID)
	{
		$self = get_called_class();

		/** @noinspection PhpUndefinedMethodInspection */
		return self::filterIDs($self::getRanges($resourceID));
	}

	/**
	 * Gets the mapped curricula ranges for the given resource
	 *
	 * @param   array|int  $identifiers  ranges of subordinate resources | resource id
	 *
	 * @return array the resource ranges
	 */
	public static function getRanges($identifiers)
	{
		$self = get_called_class();

		/** @noinspection PhpUndefinedMethodInspection */
		return $self::getRanges($identifiers);
	}

	/**
	 * Retrieves a list of options for choosing superordinate entries in the curriculum hierarchy.
	 *
	 * @param   int     $resourceID     the id of the resource for which the form is being displayed
	 * @param   string  $type           the type of the resource
	 * @param   array   $programRanges  the ranges for programs selected in the form, or already mapped
	 *
	 * @return array the superordinate resource options
	 */
	public static function getSuperOrdinateOptions(int $resourceID, string $type, array $programRanges)
	{
		$options = ['<option value="-1">' . Languages::_('ORGANIZER_NONE') . '</option>'];
		if (!$programRanges or !$type)
		{
			return $options;
		}

		$mappableRanges = self::getMappableRanges($programRanges);

		// The programs have no subordinate resources and subjects cannot be directly subordinated to programs
		if (count($mappableRanges) === count($programRanges) and $type == 'subject')
		{
			return $options;
		}

		$selected = [];

		if ($resourceID)
		{
			if ($type === 'pool')
			{
				$selected = Pools::getRanges($resourceID);

				foreach ($mappableRanges as $mIndex => $mRange)
				{
					foreach ($selected as $sRange)
					{
						if ($mRange['lft'] >= $sRange ['lft'] and $mRange['rgt'] <= $sRange ['rgt'])
						{
							unset($mappableRanges[$mIndex]);
						}
					}
				}

			}
			else
			{
				$selected = Subjects::getRanges($resourceID);
			}
		}

		$parentIDs = self::filterParentIDs($selected);

		foreach ($mappableRanges as $mappableRange)
		{

			if (!empty($mappableRange['poolID']))
			{
				$options[] = Pools::getCurricularOption($mappableRange, $parentIDs);
			}
			else
			{
				$options[] = Programs::getCurricularOption($mappableRange, $parentIDs, $type);
			}
		}

		return $options;
	}

	/**
	 * Retrieves a string value representing the degree programs to which the resource is associated.
	 *
	 * @param   int  $resourceID  the id of the resource
	 *
	 * @return string  string representing the associated program(s)
	 */
	public static function getProgramName(int $resourceID)
	{
		if (!$programs = self::getPrograms($resourceID))
		{
			return Languages::_('ORGANIZER_NO_PROGRAMS');
		}

		if (count($programs) === 1)
		{
			return Programs::getName($programs[0]['programID']);
		}
		else
		{
			return Languages::_('ORGANIZER_MULTIPLE_PROGRAMS');
		}
	}

	/**
	 * Looks up the names of the programs associated with the resource. Overwritten by the programs helper to prevent
	 * endless regression.
	 *
	 * @param   array|int  $identifiers  ranges of subordinate resources | resource id
	 *
	 * @return array the associated programs
	 */
	public static function getPrograms($identifiers)
	{
		$resource = get_called_class();

		/** @noinspection PhpUndefinedMethodInspection */
		return Programs::getRanges($resource::getRanges($identifiers));
	}

	/**
	 * Finds the curriculum entry ids for subject entries subordinate to a particular resource.
	 *
	 * @param   int  $resourceID  the id of the resource
	 * @param   int  $subjectID   the id of a specific subject resource to find in context
	 *
	 * @return array the associated programs
	 */
	public static function getSubjectIDs(int $resourceID, $subjectID = 0)
	{
		$subjects = self::getSubjects($resourceID, $subjectID);

		return self::filterIDs($subjects);
	}

	/**
	 * Finds the subject entries subordinate to a particular resource.
	 *
	 * @param   int  $resourceID  the id of the resource
	 * @param   int  $subjectID   the id of a specific subject resource to find in context
	 *
	 * @return array the associated programs
	 */
	public static function getSubjects(int $resourceID, $subjectID = 0)
	{
		$query = Database::getQuery();
		$query->select('DISTINCT *')
			->from('#__organizer_curricula')
			->order('lft');

		$resource = get_called_class();
		/** @noinspection PhpUndefinedMethodInspection */
		self::filterSubOrdinate($query, $resource::getRanges($resourceID), $subjectID);

		Database::setQuery($query);

		return Database::loadAssocList();
	}

	/**
	 * Adds a program filter clause to the given query.
	 *
	 * @param   JDatabaseQuery  $query   the query to modify
	 * @param   int             $poolID  the id of the pool to filter for
	 * @param   string          $alias   the alias of the table referenced in the join
	 *
	 * @return void modifies the query
	 */
	public static function setPoolFilter(JDatabaseQuery $query, int $poolID, string $alias)
	{
		if (!$poolID or !$ranges = Pools::getRanges($poolID))
		{
			return;
		}

		if ($poolID === self::NONE)
		{
			$query->leftJoin("#__organizer_curricula AS poc on poc.subjectID = $alias.id");
			self::filterDisassociated($query, $ranges, 'poc');

			return;
		}

		$query->innerJoin("#__organizer_curricula AS poc on poc.subjectID = $alias.id")
			->where("poc.lft > {$ranges[0]['lft']}")
			->where("poc.rgt < {$ranges[0]['rgt']}");
	}

	/**
	 * Adds a program filter clause to the given query.
	 *
	 * @param   JDatabaseQuery  $query      the query to modify
	 * @param   int             $programID  the id of the program to filter for
	 * @param   string          $context    the resource context from which this function was called
	 * @param   string          $alias      the alias of the table referenced in the join
	 *
	 * @return void modifies the query
	 */
	public static function setProgramFilter(JDatabaseQuery $query, int $programID, string $context, string $alias)
	{
		if (!$programID or !$ranges = Programs::getRanges($programID))
		{
			return;
		}

		if ($programID === self::NONE)
		{
			$query->leftJoin("#__organizer_curricula AS prc on prc.{$context}ID = $alias.id");
			self::filterDisassociated($query, $ranges, 'prc');

			return;
		}

		$query->innerJoin("#__organizer_curricula AS prc on prc.{$context}ID = $alias.id")
			->where("prc.lft > {$ranges[0]['lft']}")
			->where("prc.rgt < {$ranges[0]['rgt']}");
	}
}