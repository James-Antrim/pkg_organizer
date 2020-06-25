<?php
/**
 * @package     Organizer\Helpers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Organizer\Helpers;

use JDatabaseQuery;
use Joomla\CMS\Factory;

abstract class Curricula extends Associated implements Selectable
{
	const ALL = '-1', NONE = '-1';

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
	protected static function filterDisassociated($query, $ranges, $alias)
	{
		$erray = [];

		foreach ($ranges as $range)
		{
			$erray[] = "( $alias.lft NOT BETWEEN '{$range['lft']}' AND '{$range['rgt']}' )";
			$erray[] = "( $alias.rgt NOT BETWEEN '{$range['lft']}' AND '{$range['rgt']}' )";
		}

		$errorClauses = implode(' AND ', $erray);
		$query->where("( ($errorClauses) OR $alias.id IS NULL ) ");

		return;
	}

	/**
	 * Filters the curricula ids from an array of ranges.
	 *
	 * @param   array  $ranges  the ranges to filter
	 *
	 * @return array the curricular ids contained in the ranges
	 */
	public static function filterIDs($ranges)
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
	protected static function filterParentIDs($ranges)
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
	protected static function filterSubOrdinate($query, $ranges, $subjectID = 0)
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
	protected static function filterSuperOrdinate($query, $ranges)
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
	public static function getCurriculum(&$curriculum)
	{
		$invalidRange = (empty($curriculum['lft']) or empty($curriculum['rgt']) or $curriculum['subjectID']);
		if ($invalidRange)
		{
			return;
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('*')
			->from('#__organizer_curricula')
			->where("lft > {$curriculum['lft']}")
			->where("rgt < {$curriculum['rgt']}")
			->where("level = {$curriculum['level']} + 1")
			->order('lft');

		// Only pools should be direct subordinates of programs
		if ($curriculum['programID'])
		{
			$query->where("poolID IS NOT NULL");
		}

		$dbo->setQuery($query);

		if (!$subOrdinates = OrganizerHelper::executeQuery('loadAssocList', [], 'id'))
		{
			return;
		}

		// Fill data for subordinate resources
		foreach ($subOrdinates as &$subOrdinate)
		{
			$resourceData = $subOrdinate['poolID'] ?
				Pools::getResource($subOrdinate['poolID']) : Subjects::getResource($subOrdinate['subjectID']);

			// Avoid conflicts between the resource's actual id and the curricula table id
			unset($resourceData['id']);

			$subOrdinate = array_merge($subOrdinate, $resourceData);
			if ($subOrdinate['poolID'])
			{
				self::getCurriculum($subOrdinate);
			}
		}

		$curriculum['curriculum'] = $subOrdinates;

		return;
	}

	/**
	 * Retrieves all curriculum ranges subordinate to a program
	 *
	 * @param   array  $programRanges  the ranges of superordinate programs
	 *
	 * @return array  an array containing all ranges subordinate to the ranges specified
	 */
	private static function getMappableRanges($programRanges)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
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
			$dbo->setQuery($query);

			if (!$results = OrganizerHelper::executeQuery('loadAssocList', []))
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
	public static function getRange($rangeID)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('*')->from('#__organizer_curricula')->where("id = $rangeID");
		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssoc', []);
	}

	/**
	 * Gets the mapped curricula ranges for the given resource
	 *
	 * @param   int  $resourceID  the resource ID
	 *
	 * @return array the resource ranges
	 */
	public static function getRangeIDs($resourceID)
	{
		$self = get_called_class();

		return self::filterIDs($self::getRanges($resourceID));
	}

	/**
	 * Gets the mapped curricula ranges for the given resource
	 *
	 * @param   mixed  $identifiers  int resourceID | array ranges of subordinate resources
	 *
	 * @return array the resource ranges
	 */
	public static function getRanges($identifiers)
	{
		$self = get_called_class();

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
	public static function getSuperOrdinateOptions($resourceID, $type, $programRanges)
	{
		$options = ['<option value="-1">' . Languages::_('JNONE') . '</option>'];
		if (empty($type) or empty($programRanges))
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
	public static function getProgramName($resourceID)
	{
		if (!$programs = self::getPrograms($resourceID))
		{
			return Languages::_('JNONE');
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
	 * Looks up the names of the programs associated with the resource
	 *
	 * @param   int  $resourceID  the id of the resource
	 *
	 * @return array the associated programs
	 */
	public static function getPrograms($resourceID)
	{
		$resource = get_called_class();

		return Programs::getRanges($resource::getRanges($resourceID));
	}

	/**
	 * Finds the curriculum entry ids for subject entries subordinate to a particular resource.
	 *
	 * @param   int  $resourceID  the id of the resource
	 * @param   int  $subjectID   the id of a specific subject resource to find in context
	 *
	 * @return array the associated programs
	 */
	public static function getSubjectIDs($resourceID, $subjectID = 0)
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
	public static function getSubjects($resourceID, $subjectID = 0)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('DISTINCT *')
			->from('#__organizer_curricula')
			->order('lft');

		$resource = get_called_class();
		self::filterSubOrdinate($query, $resource::getRanges($resourceID), $subjectID);

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
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
	public static function setPoolFilter($query, $poolID, $alias)
	{
		if (empty($poolID))
		{
			return;
		}

		if (!$ranges = Pools::getRanges($poolID))
		{
			return;
		}


		if ($poolID == self::NONE)
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
	public static function setProgramFilter($query, $programID, $context, $alias)
	{
		if (empty($programID))
		{
			return;
		}

		if (!$ranges = Programs::getRanges($programID))
		{
			return;
		}


		if ($programID == self::NONE)
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