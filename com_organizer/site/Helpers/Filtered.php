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

/**
 * Class contains functions for organization filtering.
 */
trait Filtered
{
	/**
	 * Restricts the query by the organizationIDs for which the user has the given access right.
	 *
	 * @param   JDatabaseQuery  $query    the query to modify
	 * @param   string          $access   the access right to be filtered against
	 * @param   string          $context  the resource context from which this function was called
	 * @param   string          $alias    the alias being used for the resource table
	 */
	public static function addAccessFilter($query, $access, $context, $alias)
	{
		$authorized = [];

		switch ($access)
		{
			case 'document':
				$authorized = Can::documentTheseOrganizations();
				break;
			case 'manage':
				$authorized = Can::manageTheseOrganizations();
				break;
			case 'schedule':
				$authorized = Can::scheduleTheseOrganizations();
				break;
			case 'view':
				$authorized = Can::viewTheseOrganizations();
				break;
		}

		// Alias 'aaf' so as not to conflict with the access filter.
		$authorized = implode(',', $authorized);
		$query->innerJoin("#__organizer_associations AS aaf ON aaf.{$context}ID = $alias.id")
			->where("aaf.organizationID IN ($authorized)");
	}

	/**
	 * Adds a resource filter for a given resource.
	 *
	 * @param   JDatabaseQuery  $query  the query to modify
	 * @param   string          $alias  the alias for the linking table
	 */
	public static function addCampusFilter($query, $alias)
	{
		$campusID  = Input::getInt('campusID');
		$campusIDs = $campusID ? [$campusID] : Input::getFilterIDs('campus');
		if (!$campusIDs)
		{
			return;
		}

		if (in_array('-1', $campusIDs))
		{
			$query->leftJoin("#__organizer_campuses AS campusAlias ON campusAlias.id = $alias.campusID")
				->where("campusAlias.id IS NULL");
		}
		else
		{
			$campusIDs = implode(',', $campusIDs);
			$query->innerJoin("#__organizer_campuses AS campusAlias ON campusAlias.id = $alias.campusID")
				->where("(campusAlias.id IN ($campusIDs) OR campusAlias.parentID IN ($campusIDs))");
		}
	}

	/**
	 * Adds a selected organization filter to the query.
	 *
	 * @param   JDatabaseQuery  $query      the query to modify
	 * @param   string          $resource   the name of the organization associated resource
	 * @param   string          $alias      the alias being used for the resource table
	 * @param   string          $keyColumn  the name of the column holding the association key
	 *
	 * @return void modifies the query
	 */
	public static function addOrganizationFilter($query, $resource, $alias, $keyColumn = 'id')
	{
		$organizationID  = Input::getInt('organizationID');
		$organizationIDs = $organizationID ? [$organizationID] : Input::getFilterIDs('organization');
		if (empty($organizationIDs))
		{
			return;
		}

		// Alias 'aof' so as not to conflict with the access filter.
		if (in_array('-1', $organizationIDs))
		{
			$query->leftJoin("#__organizer_associations AS aof ON aof.{$resource}ID = $alias.$keyColumn")
				->where('aof.id IS NULL');
		}
		else
		{
			$query->innerJoin("#__organizer_associations AS aof ON aof.{$resource}ID = $alias.$keyColumn")
				->where("aof.organizationID IN (" . implode(',', $organizationIDs) . ")");
		}
	}

	/**
	 * Adds a resource filter for a given resource.
	 *
	 * @param   JDatabaseQuery  $query          the query to modify
	 * @param   string          $resource       the name of the resource associated
	 * @param   string          $newAlias       the alias for any linked table
	 * @param   string          $existingAlias  the alias for the linking table
	 */
	public static function addResourceFilter($query, $resource, $newAlias, $existingAlias)
	{
		// TODO Remove (plan) programs on completion of migration.
		if ($resource === 'category')
		{
			if ($categoryID = Input::getInt('programIDs') or $categoryID = Input::getInt('categoryID'))
			{
				$resourceIDs = [$categoryID];
			}

			$resourceIDs = empty($resourceIDs) ? Input::getIntCollection('categoryIDs') : $resourceIDs;
			$resourceIDs = empty($resourceIDs) ? Input::getFilterIDs('category') : $resourceIDs;
		}
		if ($resource === 'roomtype')
		{
			$default     = Input::getInt('roomTypeIDs');
			$resourceID  = Input::getInt("{$resource}ID", $default);
			$resourceIDs = $resourceID ? [$resourceID] : Input::getFilterIDs($resource);
		}

		if (empty($resourceIDs))
		{
			return;
		}

		$table = OrganizerHelper::getPlural($resource);
		if (in_array('-1', $resourceIDs))
		{
			$query->leftJoin("#__organizer_$table AS $newAlias ON $newAlias.id = $existingAlias.{$resource}ID")
				->where("$newAlias.id IS NULL");
		}
		else
		{
			$query->innerJoin("#__organizer_$table AS $newAlias ON $newAlias.id = $existingAlias.{$resource}ID")
				->where("$newAlias.id IN (" . implode(',', $resourceIDs) . ")");
		}
	}
}
