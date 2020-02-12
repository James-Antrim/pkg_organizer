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
