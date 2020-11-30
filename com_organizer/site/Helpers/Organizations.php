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
use Organizer\Adapters;
use Organizer\Tables;

/**
 * Provides general functions for organization access checks, data retrieval and display.
 */
class Organizations extends ResourceHelper implements Selectable
{
	use Numbered;

	/**
	 * Filters organizations according to user access and relevant resource associations.
	 *
	 * @param   JDatabaseQuery  $query   the query to modify
	 * @param   string          $access  any access restriction which should be performed
	 *
	 * @return void modifies the query
	 */
	private static function addAccessFilter(JDatabaseQuery $query, string $access)
	{
		if (!$access or !$view = Input::getView())
		{
			return;
		}

		$resource = OrganizerHelper::getResource($view);

		switch ($access)
		{
			case 'document':
				$query->innerJoin('#__organizer_associations AS a ON a.organizationID = o.id');
				if (in_array($resource, ['pool', 'program', 'subject']))
				{
					$query->where("a.{$resource}ID IS NOT NULL");
				}
				$allowedIDs = Can::documentTheseOrganizations();
				break;
			case 'manage':
				$allowedIDs = Can::manageTheseOrganizations();
				break;
			case 'schedule':
				$query->innerJoin('#__organizer_associations AS a ON a.organizationID = o.id');
				if (in_array($resource, ['category', 'person']))
				{
					$query->where("a.{$resource}ID IS NOT NULL");
				}
				$allowedIDs = Can::scheduleTheseOrganizations();
				break;
			case 'view':
				$allowedIDs = Can::viewTheseOrganizations();
				break;
			default:
				// Access right does not exist for organization resource.
				return;
		}

		$query->where("o.id IN ( '" . implode("', '", $allowedIDs) . "' )");
	}

	/**
	 * @inheritDoc
	 *
	 * @param   bool    $short   whether or not abbreviated names should be returned
	 * @param   string  $access  any access restriction which should be performed
	 */
	public static function getOptions($short = true, $access = '')
	{
		$options = [];
		foreach (self::getResources($access) as $organization)
		{
			$name = $short ? $organization['shortName'] : $organization['name'];

			$options[] = HTML::_('select.option', $organization['id'], $name);
		}

		uasort($options, function ($optionOne, $optionTwo) {
			return $optionOne->text > $optionTwo->text;
		});

		// Any out of sequence indexes cause JSON to treat this as an object
		return array_values($options);
	}

	/**
	 * @inheritDoc
	 *
	 * @param   string  $access  any access restriction which should be performed
	 */
	public static function getResources($access = '')
	{
		$query = Adapters\Database::getQuery();
		$tag   = Languages::getTag();
		$query->select("DISTINCT o.*, o.shortName_$tag AS shortName, o.name_$tag AS name")
			->from('#__organizer_organizations AS o');
		self::addAccessFilter($query, $access);
		Adapters\Database::setQuery($query);

		return Adapters\Database::loadAssocList();
	}

	/**
	 * Checks whether the plan resource is already associated with a organization, creating an entry if none already
	 * exists.
	 *
	 * @param   int     $resourceID  the db id for the plan resource
	 * @param   string  $column      the column in which the resource information is stored
	 *
	 * @return void
	 */
	public static function setResource(int $resourceID, string $column)
	{
		$associations = new Tables\Associations();

		/**
		 * If associations already exist for the resource, further associations should be made explicitly using the
		 * appropriate edit view.
		 */
		$data = [$column => $resourceID];
		if ($associations->load($data))
		{
			return;
		}

		// todo remove this on completion of migration
		$organizationID         = Input::getInt('organizationID');
		$data['organizationID'] = $organizationID ? $organizationID : Input::getInt('departmentID');
		$associations->save($data);
	}
}
