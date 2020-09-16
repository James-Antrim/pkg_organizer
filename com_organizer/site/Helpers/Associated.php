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
 * Ensures that resources associated with organizations have functions pertaining to those associations.
 */
abstract class Associated extends ResourceHelper
{
	use Filtered;

	static protected $resource = '';

	/**
	 * Retrieves the ids of organizations associated with the resource
	 *
	 * @param   int  $resourceID  the id of the resource for which the associated organizations are requested
	 *
	 * @return array the ids of organizations associated with the resource
	 */
	public static function getOrganizationIDs($resourceID)
	{
		$column = static::$resource . 'ID';

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('DISTINCT organizationID')
			->from('#__organizer_associations')
			->where("$column = $resourceID");
		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadColumn', []);
	}

	/**
	 * Checks whether a given resource is associated with a given organization.
	 *
	 * @param   int  $organizationID  the id of the organization
	 * @param   int  $resourceID      the id of the resource
	 *
	 * @return bool true if the resource is associated with the organization, otherwise false
	 *
	 * @since version
	 */
	public static function isAssociated($organizationID, $resourceID)
	{
		$column = static::$resource . 'ID';
		$table  = new Tables\Associations;

		return $table->load(['organizationID' => $organizationID, $column => $resourceID]) ? true : false;


	}
}
