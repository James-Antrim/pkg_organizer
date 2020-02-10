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

/**
 * Ensures that resources associated with organizations have functions pertaining to those associations.
 */
interface Associated
{
	/**
	 * Retrieves the ids of organizations associated with the resource
	 *
	 * @param   int  $resourceID  the id of the resource for which the associated organizations are requested
	 *
	 * @return array the ids of organizations associated with the resource
	 */
	public static function getOrganizationIDs($resourceID);
}
