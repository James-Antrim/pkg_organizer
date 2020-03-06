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
	protected static function filterDisassociated(&$query, $ranges, $alias)
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
	protected static function filterIDs($ranges)
	{
		$ids = [];
		foreach ($ranges as $range)
		{
			$ids[] = $range['id'];
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
	 * @param   JDatabaseQuery &$query       the query to modify
	 * @param   array           $ranges      the ranges of subordinate resources
	 * @param   string          $type        the type of subordinate resource to filter for, empty => no filter
	 * @param   int             $resourceID  the id of a specific subject resource to find in context
	 *
	 * @return void modifies the query
	 */
	protected static function filterSubOrdinate(&$query, $ranges, $type = '', $resourceID = 0)
	{
		$wherray = [];
		foreach ($ranges as $range)
		{
			$wherray[] = "( lft > '{$range['lft']}' AND rgt < '{$range['rgt']}')";
		}

		$query->where('(' . implode(' OR ', $wherray) . ')');

		if ($type and in_array($type, ['pool', 'subject']))
		{
			if ($resourceID)
			{
				$query->where("{$type}ID = $resourceID");
			}
			else
			{
				$query->where("{$type}ID IS NOT NULL");
			}
		}
	}

	/**
	 * Adds range restrictions for subordinate resources.
	 *
	 * @param   JDatabaseQuery &$query   the query to modify
	 * @param   array           $ranges  the ranges of subordinate resources
	 *
	 * @return void modifies the query
	 */
	protected static function filterSuperOrdinate(&$query, $ranges)
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

		if (!$subordinateResources = OrganizerHelper::executeQuery('loadAssocList', [], 'id'))
		{
			return;
		}

		// Fill data for subordinate resources
		foreach ($subordinateResources as &$resource)
		{
			$resourceData = $resource['poolID'] ?
				Pools::getResource($resource['poolID']) : Subjects::getResource($resource['subjectID']);

			// Avoid conflicts between the resource's actual id and the curricula table id
			unset($resourceData['id']);

			$resource = array_merge($resource, $resourceData);
			if ($resource['poolID'])
			{
				self::getCurriculum($resource);
			}
		}

		$curriculum['curriculum'] = $subordinateResources;

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
	 * Retrieves the ids of all subordinate resource ranges.
	 *
	 * @param   array  $ranges  the current ranges of the pool
	 *
	 * @return array  the ids of the subordinate resource ranges
	 */
	public static function getSubOrdinateIDs($ranges)
	{
		$dbo = Factory::getDbo();

		$query = $dbo->getQuery(true);
		$query->select('id')->from('#__organizer_curricula');
		$dbo->setQuery($query);

		$subordinateIDs = [];
		foreach ($ranges as $range)
		{
			$query->clear('where');
			$query->where("lft > {$range['lft']}")
				->where("rgt < {$range['rgt']}")
				->order('lft ASC');
			$dbo->setQuery($query);

			if (!$results = OrganizerHelper::executeQuery('loadAssocList', []))
			{
				continue;
			}

			$subordinateIDs = array_merge($subordinateIDs, $results);
		}

		return OrganizerHelper::executeQuery('loadColumn', []);
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
		if (empty($resourceID) or empty($type) or empty($programRanges))
		{
			return $options;
		}

		$mappableRanges     = self::getMappableRanges($programRanges);
		$onlyProgramsMapped = count($mappableRanges) === count($programRanges);

		// Subjects cannot be subordinated to programs
		if ($onlyProgramsMapped and $type == 'subject')
		{
			return $options;
		}

		if ($type === 'pool')
		{
			$selected = Pools::getFilteredRanges($resourceID);

			$curriculumIDs  = self::filterIDs($selected);
			$subordinateIDs = self::getSubOrdinateIDs($selected);

			// Pools cannot be subordinated to themselves or any pool subordinated to them.
			$suppressIDs = array_merge($curriculumIDs, $subordinateIDs);
		}
		else
		{
			$selected    = Subjects::getRanges($resourceID);
			$suppressIDs = [];
		}

		$parentIDs = self::filterParentIDs($selected);

		foreach ($mappableRanges as $mappableRange)
		{
			if (in_array($mappableRange['id'], $suppressIDs))
			{
				continue;
			}

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
	 * Finds the subject entries subordinate to a particular resource.
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
			->where('subjectID IS NOT NULL ')
			->order('lft');

		$resource = get_called_class();
		self::filterSubOrdinate($query, $resource::getRanges($resourceID), 'subject', $subjectID);

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
	}

	/**
	 * Adds a program filter clause to the given query.
	 *
	 * @param   JDatabaseQuery  $query   the query to be modified
	 * @param   int             $poolID  the id of the pool to filter for
	 * @param   string          $alias   the alias of the table referenced in the join
	 *
	 * @return void modifies the query
	 */
	public static function setPoolFilter(&$query, $poolID, $alias)
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
	 * @param   JDatabaseQuery  $query      the query to be modified
	 * @param   int             $programID  the id of the program to filter for
	 * @param   string          $context    the resource context from which this function was called
	 * @param   string          $alias      the alias of the table referenced in the join
	 *
	 * @return void modifies the query
	 */
	public static function setProgramFilter(&$query, $programID, $context, $alias)
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