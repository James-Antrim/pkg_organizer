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

use Joomla\Utilities\ArrayHelper;
use Organizer\Adapters\Database;
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
	public static function administrate(): bool
	{
		$user = Users::getUser();

		if (!$user->id)
		{
			return false;
		}

		return ($user->authorise('core.admin') or $user->authorise('core.admin', 'com_organizer'));
	}

	/**
	 * Performs ubiquitous authorization checks. Functions using this with is_bool() will get a false positive for a null
	 * return value in their own return value suggestions.
	 *
	 * @return bool|null true if the user has administrative authorization, false if the user is a guest, otherwise null
	 */
	private static function basic(): ?bool
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
	 * Checks whether the user has access to documentation resources and their respective views.
	 *
	 * @param   string  $resourceType  the resource type being checked
	 * @param   int     $resourceID    the resource id being checked or an array if resource ids to check
	 *
	 * @return bool true if the user is authorized to document the resources of an/the organization
	 */
	public static function document(string $resourceType, int $resourceID = 0): bool
	{
		if (is_bool($authorized = self::basic()))
		{
			return $authorized;
		}

		if (!in_array($resourceType, ['fieldcolor', 'organization', 'pool', 'program', 'subject']))
		{
			return false;
		}

		$query = Database::getQuery();
		$query->select('DISTINCT organizationID')->from('#__organizer_associations');
		$organizationIDs = [];

		if ($resourceID)
		{
			switch ($resourceType)
			{
				case 'fieldcolor':
					$table = new Tables\FieldColors();

					if (!$table->load($resourceID) or empty($table->organizationID))
					{
						return false;
					}

					$organizationIDs[] = $table->organizationID;
					break;
				case 'organization':
					$organizationIDs[] = $resourceID;
					break;
				case 'pool':
					$query->where("poolID = $resourceID");
					Database::setQuery($query);

					if (!$organizationIDs = Database::loadIntColumn())
					{
						return false;
					}

					break;
				case 'program':
					$query->where("programID = $resourceID");
					Database::setQuery($query);

					if (!$organizationIDs = Database::loadIntColumn())
					{
						return false;
					}

					break;
				case 'subject':

					if (Subjects::coordinates($resourceID))
					{
						return true;
					}

					$query->where("subjectID = $resourceID");
					Database::setQuery($query);

					if (!$organizationIDs = Database::loadIntColumn())
					{
						return false;
					}

					break;
				default:
					return false;
			}

			if (!$organizationIDs)
			{
				return false;
			}
		}
		else
		{
			Database::setQuery($query);
			$organizationIDs = Database::loadIntColumn();
		}

		$user = Users::getUser();

		foreach ($organizationIDs as $organizationID)
		{
			if ($user->authorise('organizer.document', "com_organizer.organization.$organizationID"))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets the ids of organizations for which the user is authorized documentation access
	 *
	 * @return array  the organization ids, empty if user has no access
	 */
	public static function documentTheseOrganizations(): array
	{
		return self::getAuthorizedOrganizations('document');
	}

	/**
	 * Checks whether the user has access to the participant information
	 *
	 * @param   string          $resourceType  the resource type being checked
	 * @param   array|int|null  $resource      the resource id being checked or an array if resource ids to check
	 *
	 * @return bool true if the user is authorized to manage courses, otherwise false
	 */
	public static function edit(string $resourceType, $resource = null): bool
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

			case 'course':
			case 'courses':

				return (Courses::coordinates($resource) or Courses::hasResponsibility($resource));

			case 'event':
			case 'events':

				return $resource ? Events::coordinates($resource) : Events::coordinates() or self::scheduleTheseOrganizations();

			case 'group':
			case 'groups':

				return self::editScheduleResource('Groups', $resource);

			case 'participant':

				if (!is_numeric($resource))
				{
					return false;
				}

				if ($user->id == $resource)
				{
					return true;
				}

				return self::manage($resourceType, $resource);

			case 'person':
			case 'persons':

				return $resource ? self::editScheduleResource('Persons', $resource) : self::manage('persons');
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
	 * @noinspection PhpUndefinedMethodInspection
	 */
	private static function editScheduleResource(string $helperClass, $resource): bool
	{
		if (empty($resource))
		{
			return false;
		}

		$authorized = self::scheduleTheseOrganizations();
		$helper     = "Organizer\\Helpers\\$helperClass";

		if (is_numeric($resource) and $resource = intval($resource))
		{
			$associated = $helper::getOrganizationIDs($resource);

			return (bool) array_intersect($associated, $authorized);
		}
		elseif (is_array($resource))
		{
			$resource = ArrayHelper::toInteger($resource);

			foreach ($resource as $resourceID)
			{
				if (!array_intersect($helper::getOrganizationIDs($resourceID), $authorized))
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
	private static function getAuthorizedOrganizations(string $function): array
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
	 * @param   string  $resourceType  the resource type being checked
	 * @param   int     $resourceID    the resource id being checked or an array if resource ids to check
	 *
	 * @return bool true if the user is authorized for scheduling functions and views.
	 */
	public static function manage(string $resourceType, int $resourceID = 0): bool
	{
		if (is_bool($authorized = self::basic()))
		{
			return $authorized;
		}

		switch ($resourceType)
		{
			case 'booking':
			case 'bookings':
				foreach (Bookings::getInstanceIDs($resourceID) as $instanceID)
				{
					if (self::manage('instance', $instanceID))
					{
						return true;
					}
				}

				return false;
			case 'course':
			case 'courses':
				return (Courses::coordinates($resourceID) or Courses::hasResponsibility($resourceID));
			case 'instance':
			case 'instances':
				if (Instances::hasResponsibility($resourceID))
				{
					return true;
				}

				$instanceOrganizations = Instances::getOrganizationIDs($resourceID);
				$managedOrganizations  = self::manageTheseOrganizations();

				return (bool) array_intersect($managedOrganizations, $instanceOrganizations);
			case 'facilities':
				return Users::getUser()->authorise('organizer.fm', 'com_organizer');
			case 'organization':
				return Users::getUser()->authorise('organizer.manage', "com_organizer.organization.$resourceID");
			case 'participant':
				if ($resourceID === Users::getID())
				{
					return true;
				}

				$courseIDs = Participants::getCourses($resourceID);

				foreach ($courseIDs as $courseID)
				{
					if (Courses::coordinates($courseID))
					{
						return true;
					}
				}

				return false;
			case 'persons':
				return Users::getUser()->authorise('organizer.hr', 'com_organizer');
			case 'unit':
			case 'units':
				if (Units::teaches($resourceID))
				{
					return true;
				}

				return in_array(Units::getOrganizationID($resourceID), self::manageTheseOrganizations());
			default:
				return false;
		}
	}

	/**
	 * Gets the ids of organizations for which the user is authorized managing access
	 *
	 * @return array  the organization ids, empty if user has no access
	 */
	public static function manageTheseOrganizations(): array
	{
		return self::getAuthorizedOrganizations('manage');
	}

	/**
	 * Checks whether the user has access to scheduling resources and their respective views.
	 *
	 * @param   string  $resourceType  the resource type being checked
	 * @param   int     $resourceID    the resource id being checked or an array if resource ids to check
	 *
	 * @return bool true if the user is authorized for scheduling functions and views.
	 */
	public static function schedule(string $resourceType, int $resourceID): bool
	{
		if (is_bool($authorized = self::basic()))
		{
			return $authorized;
		}

		if (!$resourceID)
		{
			return false;
		}

		$user = Users::getUser();

		if ($resourceType === 'schedule')
		{
			$schedule = new Tables\Schedules();

			if (!$schedule->load($resourceID))
			{
				return false;
			}

			return $user->authorise('organizer.schedule', "com_organizer.organization.$schedule->organizationID");
		}

		if ($resourceType === 'organization')
		{
			return $user->authorise('organizer.schedule', "com_organizer.organization.$resourceID");
		}

		return false;
	}

	/**
	 * Gets the ids of organizations for which the user is authorized scheduling access
	 *
	 * @return array  the organization ids, empty if user has no access
	 */
	public static function scheduleTheseOrganizations(): array
	{
		return self::getAuthorizedOrganizations('schedule');
	}

	/**
	 * Check whether the user is authorized to perform contact tracing.
	 *
	 * @return bool|null
	 */
	public static function traceContacts(): bool
	{
		if (is_bool($authorized = self::basic()))
		{
			return $authorized;
		}

		return Users::getUser()->authorise('organizer.ct', 'com_organizer');
	}

	/**
	 * Checks whether the user has privileged access to resource associated views.
	 *
	 * @param   string  $resourceType  the resource type being checked
	 * @param   int     $resourceID    the resource id being checked or an array if resource ids to check
	 *
	 * @return bool true if the user is authorized for scheduling functions and views.
	 */
	public static function view(string $resourceType, int $resourceID): bool
	{
		if (is_bool($authorized = self::basic()))
		{
			return $authorized;
		}

		$user = Users::getUser();

		if ($resourceType === 'organization')
		{
			if ($user->authorise('organizer.view', "com_organizer.organization.$resourceID"))
			{
				return true;
			}

			return self::manage($resourceType, $resourceID);
		}

		return false;
	}

	/**
	 * Gets the ids of organizations for which the user is authorized privileged view access
	 *
	 * @return array  the organization ids, empty if user has no access
	 */
	public static function viewTheseOrganizations(): array
	{
		return self::getAuthorizedOrganizations('view');
	}
}
