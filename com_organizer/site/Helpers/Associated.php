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

use Organizer\Adapters\Database;
use Organizer\Tables;

/**
 * Ensures that resources associated with organizations have functions pertaining to those associations.
 */
abstract class Associated extends ResourceHelper
{
	use Filtered;

	protected static $resource = '';

	/**
	 * Retrieves the ids of organizations associated with the resource
	 *
	 * @param   int  $resourceID  the id of the resource for which the associated organizations are requested
	 *
	 * @return int[] the ids of organizations associated with the resource
	 */
	public static function getOrganizationIDs(int $resourceID): array
	{
		$column = static::$resource . 'ID';
		$query  = Database::getQuery(true);
		$query->select('DISTINCT organizationID')
			->from('#__organizer_associations')
			->where("$column = $resourceID");
		Database::setQuery($query);

		return Database::loadIntColumn();
	}

	/**
	 * Checks whether a given resource is associated with a given organization.
	 *
	 * @param   int  $organizationID  the id of the organization
	 * @param   int  $resourceID      the id of the resource
	 *
	 * @return bool true if the resource is associated with the organization, otherwise false
	 */
	public static function isAssociated(int $organizationID, int $resourceID): bool
	{
		$column = static::$resource . 'ID';
		$table  = new Tables\Associations();

		return $table->load(['organizationID' => $organizationID, $column => $resourceID]);
	}
}
