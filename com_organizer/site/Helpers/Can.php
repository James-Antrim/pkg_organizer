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
 * Class provides generalized functions useful for several component files.
 */
class Can
{
	/**
	 * Checks whether the user is an authorized administrator.
	 *
	 * @return bool true if the user is an administrator, otherwise false
	 */
	public static function administrate()
	{
		$user = Users::getUser();
		if (!$user->id)
		{
			return false;
		}


		return ($user->authorise('core.admin') or $user->authorise('core.admin', 'com_organizer'));
	}

	/**
	 * Performs ubiquitous authorization checks.
	 *
	 * @return bool|null true if the user has administrative authorization, false if the user is a guest, otherwise null
	 */
	private static function basic()
	{
		if (!Users::getID())
		{
			return false;
		}

		if (self::administrate())
		{
			return true;
		}

		return null;
	}

	/**
	 * Checks for resources which have not yet been saved as an asset allowing transitional edit access
	 *
	 * @param   string  $resourceName  the name of the resource type
	 * @param   int     $itemID        the id of the item being checked
	 *
	 * @return bool  true if the resource has an associated asset, otherwise false
	 */
	private static function isInitialized($resourceName, $itemID)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('asset_id')->from("#__organizer_{$resourceName}s")->where("id = '$itemID'");
		$dbo->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('loadResult');
	}

	/**
	 * Checks whether the user has access to documentation resources and their respective views.
	 *
	 * @param   string     $resourceType  the resource type being checked
	 * @param   array|int  $resourceID    the resource id being checked or an array if resource ids to check
	 *
	 * @return bool true if the user is authorized for facility management functions and views.
	 */
	public static function document($resourceType, $resourceID = null)
	{
		if (is_bool($authorized = self::basic()))
		{
			return $authorized;
		}

		$invalidID   = (empty($resourceID) or !is_numeric($resourceID));
		$invalidType = !in_array($resourceType, ['fieldColor', 'organization', 'pool', 'program', 'subject']);
		if ($invalidID or $invalidType)
		{
			return false;
		}

		$user = Users::getUser();

		if ($resourceType === 'organization')
		{
			$organizationID = $resourceID;
		}
		else
		{
			$table = null;
			switch ($resourceType)
			{
				case 'fieldColor':
					$table = new Tables\FieldColors;
					break;
				case 'pool':
					$table = new Tables\Pools;
					break;
				case 'program':
					$table = new Tables\Programs;
					break;
				case 'subject':
					/*if (Subjects::coordinates($resourceID))
					{
						return true;
					}*/
					$table = new Tables\Subjects;
					break;
				default:
					return false;
			}

			if (!$table->load($resourceID) or empty($table->organizationID))
			{
				return false;
			}

			$organizationID = $table->organizationID;
		}


		return $user->authorise('organizer.document', "com_organizer.organization.$organizationID");
	}

	/**
	 * Gets the ids of organizations for which the user is authorized documentation access
	 *
	 * @return array  the organization ids, empty if user has no access
	 */
	public static function documentTheseOrganizations()
	{
		return self::getAuthorizedOrganizations('document');
	}

	/**
	 * Checks whether the user has access to the participant information
	 *
	 * @param   string     $resourceType  the resource type being checked
	 * @param   array|int  $resource      the resource id being checked or an array if resource ids to check
	 *
	 * @return bool true if the user is authorized to manage courses, otherwise false
	 */
	public static function edit($resourceType, $resource = null)
	{
		if (is_bool($authorized = self::basic()))
		{
			return $authorized;
		}

		$user = Users::getUser();

		switch ($resourceType)
		{
			case 'categories':
			case 'category':

				return self::editScheduleResource('Categories', $resource);

			case 'event':
			case 'events':

				return self::editScheduleResource('Events', $resource);

			case 'group':
			case 'groups':

				return self::editScheduleResource('Groups', $resource);

			case 'participant':

				if (!is_numeric($resource))
				{
					return false;
				}

				if ($user->id === $resource)
				{
					return true;
				}

				return self::manage($resourceType, $resource);

			case 'person':
			case 'persons':

				if (self::manage('persons'))
				{
					return true;
				}

				return self::editScheduleResource('Persons', $resource);
		}

		return false;
	}

	/**
	 * Returns whether the user is authorized to edit the schedule resource.
	 *
	 * @param   string     $helperClass  the name of the helper class
	 * @param   array|int  $resource     the resource id being checked or an array if resource ids to check
	 *
	 * @return bool true if the user is authorized to manage courses, otherwise false
	 */
	private static function editScheduleResource($helperClass, $resource)
	{
		if (empty($resource))
		{
			return false;
		}

		$authorized = self::scheduleTheseOrganizations();
		$helper     = "Organizer\\Helpers\\$helperClass";

		if (is_int($resource))
		{
			$associated = $helper::getOrganizationIDs($resource);

			return (bool) array_intersect($associated, $authorized);
		}
		elseif (is_array($resource))
		{
			foreach ($resource as $resourceID)
			{
				$associated = $helper::getOrganizationIDs($resourceID);
				if (!array_intersect($associated, $authorized))
				{
					return false;
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * Gets the organization ids of for which the user is authorized access
	 *
	 * @param   string  $function  the action for authorization
	 *
	 * @return array  the organization ids, empty if user has no access
	 */
	private static function getAuthorizedOrganizations($function)
	{
		if (!Users::getID())
		{
			return [];
		}

		$organizationIDs = Organizations::getIDs();

		if (self::administrate())
		{
			return $organizationIDs;
		}

		if (!method_exists('Organizer\\Helpers\\Can', $function))
		{
			return [];
		}

		$authorized = [];

		foreach ($organizationIDs as $organizationID)
		{
			if (self::$function('organization', $organizationID))
			{
				$authorized[] = $organizationID;
			}
		}

		return $authorized;
	}

	/**
	 * Checks whether the user can manage the given resource.
	 *
	 * @param   string     $resourceType  the resource type being checked
	 * @param   array|int  $resource      the resource id being checked or an array if resource ids to check
	 *
	 * @return bool true if the user is authorized for scheduling functions and views.
	 */
	public static function manage($resourceType, $resource = null)
	{
		if (is_bool($authorized = self::basic()))
		{
			return $authorized;
		}

		$user = Users::getUser();

		if ($resourceType === 'courses' or $resourceType === 'course')
		{
			return (Courses::coordinates($resource) or Courses::hasResponsibility($resource));
		}

		if ($resourceType === 'organization' and is_int($resource))
		{
			return $user->authorise('organizer.manage', "com_organizer.organization.$resource");
		}

		if ($resourceType === 'facilities')
		{
			return $user->authorise('organizer.fm', 'com_organizer');
		}

		if ($resourceType === 'participant' and is_int($resource))
		{
			$participantCourses = Participants::getCourses($resource);

			foreach ($participantCourses as $courseID)
			{
				if (Courses::coordinates($courseID))
				{
					return true;
				}
			}

			return false;
		}

		if ($resourceType === 'persons')
		{
			return $user->authorise('organizer.hr', 'com_organizer');
		}

		return false;
	}

	/**
	 * Gets the ids of organizations for which the user is authorized managing access
	 *
	 * @return array  the organization ids, empty if user has no access
	 */
	public static function manageTheseOrganizations()
	{
		return self::getAuthorizedOrganizations('manage');
	}

	/**
	 * Checks whether the user has access to scheduling resources and their respective views.
	 *
	 * @param   string     $resourceType  the resource type being checked
	 * @param   array|int  $resource      the resource id being checked or an array if resource ids to check
	 *
	 * @return bool true if the user is authorized for scheduling functions and views.
	 */
	public static function schedule($resourceType, $resource = null)
	{
		if (is_bool($authorized = self::basic()))
		{
			return $authorized;
		}

		if (!$resource)
		{
			return false;
		}

		$user = Users::getUser();

		if ($resourceType === 'schedule')
		{
			return $user->authorise('organizer.schedule', "com_organizer.schedule.$resource");
		}

		if ($resourceType === 'organization')
		{
			return $user->authorise('organizer.schedule', "com_organizer.organization.$resource");
		}

		return false;
	}

	/**
	 * Gets the ids of organizations for which the user is authorized scheduling access
	 *
	 * @return array  the organization ids, empty if user has no access
	 */
	public static function scheduleTheseOrganizations()
	{
		return self::getAuthorizedOrganizations('schedule');
	}

	/**
	 * Checks whether the user has privileged access to resource associated views.
	 *
	 * @param   string     $resourceType  the resource type being checked
	 * @param   array|int  $resource      the resource id being checked or an array if resource ids to check
	 *
	 * @return bool true if the user is authorized for scheduling functions and views.
	 */
	public static function view($resourceType, $resource = null)
	{
		if (is_bool($authorized = self::basic()))
		{
			return $authorized;
		}

		$user = Users::getUser();

		if ($resourceType === 'organization' and is_int($resource))
		{
			if ($user->authorise('organizer.view', "com_organizer.organization.$resource"))
			{
				return true;
			}

			return self::manage($resourceType, $resource);
		}

		return false;
	}

	/**
	 * Gets the ids of organizations for which the user is authorized privileged view access
	 *
	 * @return array  the organization ids, empty if user has no access
	 */
	public static function viewTheseOrganizations()
	{
		return self::getAuthorizedOrganizations('view');
	}
}
