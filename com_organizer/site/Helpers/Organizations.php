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
use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;
use Organizer\Tables\Associations;

/**
 * Provides general functions for department access checks, data retrieval and display.
 */
class Organizations extends ResourceHelper implements Selectable
{
	/**
	 * Filters departments according to user access and relevant resource associations.
	 *
	 * @param   JDatabaseQuery &$query   the query to be modified.
	 * @param   string          $access  any access restriction which should be performed
	 *
	 * @return void modifies the query
	 */
	private static function addAccessFilter(&$query, $access)
	{
		$view = Input::getView();
		if (empty($access) or empty($view))
		{
			return;
		}

		$resource = OrganizerHelper::getResource($view);

		switch ($access)
		{
			case 'document':
				$table = OrganizerHelper::getPlural($resource);
				$query->innerJoin("#__organizer_$table AS res ON res.organizationID = depts.id");
				$allowedIDs = Can::documentTheseOrganizations();
				break;
			case 'manage':
				$allowedIDs = Can::manageTheseOrganizations();
				break;
			case 'schedule':
				$query->innerJoin('#__organizer_associations AS a ON a.organizationID = depts.id');
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
				// Access right does not exist for department resource.
				return;
		}

		$query->where("depts.id IN ( '" . implode("', '", $allowedIDs) . "' )");
	}

	/**
	 * Retrieves the resource items.
	 *
	 * @param   string  $access  any access restriction which should be performed
	 *
	 * @return array the available resources
	 */
	public static function getIDs()
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('id')->from('#__organizer_departments');

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadColumn', []);
	}

	/**
	 * Retrieves the selectable options for the resource.
	 *
	 * @param   bool    $short   whether or not abbreviated names should be returned
	 * @param   string  $access  any access restriction which should be performed
	 *
	 * @return array the available options
	 */
	public static function getOptions($short = true, $access = '')
	{
		$options = [];
		foreach (self::getResources($access) as $department)
		{
			$name = $short ? $department['shortName'] : $department['name'];

			$options[] = HTML::_('select.option', $department['id'], $name);
		}

		uasort($options, function ($optionOne, $optionTwo) {
			return $optionOne->text > $optionTwo->text;
		});

		// Any out of sequence indexes cause JSON to treat this as an object
		return array_values($options);
	}

	/**
	 * Retrieves the resource items.
	 *
	 * @param   string  $access  any access restriction which should be performed
	 *
	 * @return array the available resources
	 */
	public static function getResources($access = '')
	{
		$dbo   = Factory::getDbo();
		$tag   = Languages::getTag();
		$query = $dbo->getQuery(true);

		$query->select("DISTINCT depts.*, depts.shortName_$tag AS shortName, depts.name_$tag AS name")
			->from('#__organizer_departments AS depts');

		self::addAccessFilter($query, $access);

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
	}

	/**
	 * Checks whether the plan resource is already associated with a department, creating an entry if none already
	 * exists.
	 *
	 * @param   int     $resourceID  the db id for the plan resource
	 * @param   string  $column      the column in which the resource information is stored
	 *
	 * @return void
	 */
	public static function setResource($resourceID, $column)
	{
		$deptResourceTable = new Associations;

		/**
		 * If associations already exist for the resource, further associations should be made explicitly using the
		 * appropriate edit view.
		 */
		$data = [$column => $resourceID];
		if ($deptResourceTable->load($data))
		{
			return;
		}

		$data['organizationID'] = Input::getInt('organizationID');

		try
		{
			$deptResourceTable->save($data);
		}
		catch (Exception $exc)
		{
			OrganizerHelper::message($exc->getMessage(), 'error');
		}

		return;
	}
}
