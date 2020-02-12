<?php
/**
 * @package     Organizer\Helpers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Organizer\Helpers;


use Joomla\CMS\Factory;

abstract class Curricula extends ResourceHelper implements Selectable
{
	const ALL = '-1';

	/**
	 * Adds range restrictions for subordinate resources.
	 *
	 * @param   JDatabaseQuery &$query   the query to modify
	 * @param   array           $ranges  the ranges of subordinate resources
	 * @param   string          $type    the type of subordinate resource to filter for, empty => no filter
	 *
	 * @return void modifies the query
	 */
	private static function filterSubOrdinate(&$query, $ranges, $type = '')
	{
		$wherray = [];
		foreach ($ranges as $range)
		{
			$wherray[] = "( lft > '{$range['lft']}' AND rgt < '{$range['rgt']}')";
		}

		$query->where('(' . implode(' OR ', $wherray) . ')');

		if ($type and in_array($type, ['pool', 'subject']))
		{
			$query->where("{$type}ID IS NOT NULL");
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
	 * Gets the mapped curricula ranges for the given resource
	 *
	 * @param   mixed  $identifiers  int resourceID | array ranges of subordinate resources
	 *
	 * @return array the resource ranges
	 */
	abstract public static function getRanges($identifiers);

	/**
	 * Retrieves a string value representing the degree programs to which the resource is associated.
	 *
	 * @param   int  $resourceID  the id of the resource
	 *
	 * @return string  string representing the associated program(s)
	 */
	public static function getProgramName($resourceID)
	{
		$programs = self::getPrograms($resourceID);
		if (empty($programs))
		{
			return Languages::_('JNONE');
		}

		if (count($programs) === 1)
		{
			return Programs::getName($programs[0]['id']);
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
	 * Looks up the names of the subjects associated with the resource
	 *
	 * @param   int  $resourceID  the id of the resource
	 *
	 * @return array the associated programs
	 */
	public static function getSubjects($resourceID)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('DISTINCT *')
			->from('#__organizer_curricula')
			->where('subjectID IS NOT NULL ')
			->order('lft');

		$resource = get_called_class();
		self::filterSubOrdinate($query, $resource::getRanges($resourceID), 'subject');

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
	}

}