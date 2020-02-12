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
use Organizer\Tables\Pools as PoolsTable;

/**
 * Provides general functions for mapping data retrieval.
 */
class Mappings
{
	/**
	 * Retrieves the ids of both direct and indirect pool children
	 *
	 * @param   array &$mappings  the current mappings of the pool
	 *
	 * @return array  the ids of the children of a pool
	 */
	public static function getChildMappingIDs(&$mappings)
	{
		$dbo = Factory::getDbo();

		// The children should be the same regardless of which mapping is used, so we just take the last one
		$mapping = array_pop($mappings);

		// If mappings was empty mapping can be null
		if (empty($mapping))
		{
			return [];
		}

		$childrenQuery = $dbo->getQuery(true);
		$childrenQuery->select('id')->from('#__organizer_mappings');
		$childrenQuery->where("lft > '{$mapping['lft']}'");
		$childrenQuery->where("rgt < '{$mapping['rgt']}'");
		$dbo->setQuery($childrenQuery);

		return OrganizerHelper::executeQuery('loadColumn', []);
	}

	/**
	 * Retrieves the ids of both direct and indirect pool children
	 *
	 * @param   array &$resource  the current mappings of the pool
	 *
	 * @return void  modifies the resource
	 */
	public static function getChildren(&$resource)
	{
		$invalidMapping = (empty($resource['lft']) or empty($resource['rgt']));
		$isSubject      = !empty($resource['subjectID']);
		if ($invalidMapping or $isSubject)
		{
			return;
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('*')
			->from('#__organizer_mappings')
			->where("lft > '{$resource['lft']}'")
			->where("rgt < '{$resource['rgt']}'")
			->where("level = {$resource['level']} + 1")
			->order('lft');

		if (!empty($resource['programID']))
		{
			$query->where("poolID IS NOT NULL");
		}

		$dbo->setQuery($query);

		$mappings = OrganizerHelper::executeQuery('loadAssocList', [], 'id');

		if (empty($mappings))
		{
			return;
		}

		foreach ($mappings as $id => &$mapping)
		{
			$attributes = $mapping['poolID'] ?
				Pools::getResource($mapping['poolID']) : Subjects::getResource($mapping['subjectID']);
			unset($attributes['id']);
			$mapping = array_merge($mapping, $attributes);
			if ($mapping['poolID'])
			{
				self::getChildren($mapping);
			}
		}

		$resource['children'] = $mappings;

		return;
	}

	/**
	 * Provides an indentation according to the structural depth of a pool
	 *
	 * @param   string  $name          the name of the pool
	 * @param   int     $level         the pool's structural depth
	 * @param   bool    $withPrograms  if programs will be listed with the pools
	 *
	 * @return string
	 */
	public static function getIndentedPoolName($name, $level, $withPrograms = true)
	{
		if ($level == 1 and $withPrograms == false)
		{
			return $name;
		}

		$iteration = $withPrograms ? 0 : 1;
		$indent    = '';
		while ($iteration < $level)
		{
			$indent .= '&nbsp;&nbsp;&nbsp;';
			$iteration++;
		}

		return $indent . '|_' . $name;
	}

	/**
	 * Gets a HTML option based upon a pool mapping
	 *
	 * @param   array &$mapping          the pool mapping entry
	 * @param   array &$selectedParents  the selected parents
	 *
	 * @return string  HTML option
	 */
	public static function getPoolOption(&$mapping, &$selectedParents)
	{
		$tag        = Languages::getTag();
		$poolsTable = new PoolsTable;

		try
		{
			$poolsTable->load($mapping['poolID']);
		}
		catch (Exception $exc)
		{
			OrganizerHelper::message($exc->getMessage(), 'error');

			return '';
		}

		$nameColumn   = "name_$tag";
		$indentedName = self::getIndentedPoolName($poolsTable->$nameColumn, $mapping['level']);

		$selected = in_array($mapping['id'], $selectedParents) ? 'selected' : '';

		return "<option value='{$mapping['id']}' $selected>$indentedName</option>";
	}

	/**
	 * Retrieves all mapping entries subordinate to associated degree programs
	 *
	 * @param   array &$programEntries  the program mappings themselves
	 *
	 * @return array  an array containing information for all program mappings
	 */
	public static function getProgramMappings(&$programEntries)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('*');
		$query->from('#__organizer_mappings');

		$programMappings = [];
		foreach ($programEntries as $programEntry)
		{
			$query->clear('where');
			$query->where("lft >= '{$programEntry['lft']}'");
			$query->where("rgt <= '{$programEntry['rgt']}'");
			$query->order('lft ASC');
			$dbo->setQuery($query);

			$results = OrganizerHelper::executeQuery('loadAssocList');
			if (empty($results))
			{
				continue;
			}

			$programMappings = array_merge($programMappings, empty($results) ? [] : $results);
		}

		return $programMappings;
	}

	/**
	 * Gets a HTML option based upon a program mapping
	 *
	 * @param   array  &$mapping          the program mapping entry
	 * @param   array  &$selectedParents  the selected parents
	 * @param   string  $resourceType     the type of resource
	 *
	 * @return string  HTML option
	 */
	public static function getProgramOption(&$mapping, &$selectedParents, $resourceType)
	{
		$dbo   = Factory::getDbo();
		$query = Programs::getProgramQuery();

		$query->where("p.id = '{$mapping['programID']}'");
		$dbo->setQuery($query);

		$name = OrganizerHelper::executeQuery('loadResult');

		if (empty($name))
		{
			return '';
		}

		if ($resourceType == 'subject')
		{
			$selected = '';
			$disabled = 'disabled';
		}
		else
		{
			$selected = in_array($mapping['id'], $selectedParents) ? 'selected' : '';
			$disabled = '';
		}

		return "<option value='{$mapping['id']}' $selected $disabled>$name</option>";
	}

	/**
	 * Retrieves the parent ids of the resource in question. Used in parentpool field.
	 *
	 * @param   int     $resourceID    the resource id
	 * @param   string  $resourceType  the type of resource
	 * @param   array  &$mappings      an array to store the mappings in
	 * @param   array  &$mappingIDs    an array to store the mapping ids in
	 * @param   array  &$parentIDs     an array to store the parent ids in
	 *
	 * @return void
	 */
	public static function setMappingData($resourceID, $resourceType, &$mappings, &$mappingIDs, &$parentIDs)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('id, parentID, lft, rgt');
		$query->from('#__organizer_mappings');
		$query->where("{$resourceType}ID = '$resourceID'");
		$dbo->setQuery($query);
		$mappings   = array_merge($mappings, OrganizerHelper::executeQuery('loadAssocList', []));
		$mappingIDs = array_merge($mappingIDs, OrganizerHelper::executeQuery('loadColumn', []));
		$parentIDs  = array_merge($parentIDs, OrganizerHelper::executeQuery('loadColumn', [], 1));
	}
}
