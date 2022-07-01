<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Organizer\Helpers;
use Organizer\Tables\Associations;

/**
 * Class standardizes the getName function across classes.
 */
trait Associated
{
	/**
	 * Sets context variables as requested.
	 *
	 * @param   int    $resourceID       the id of the resource being processed
	 * @param   array  $organizationIDs  the organization ids with which the resource should be associated
	 *
	 * @return bool true on success, otherwise false
	 */
	protected function updateAssociations($resourceID, $organizationIDs): bool
	{
		foreach (Helpers\Organizations::getIDs() as $organizationID)
		{
			$conditions = ["{$this->resource}ID" => $resourceID, 'organizationID' => $organizationID];
			$requested  = in_array($organizationID, $organizationIDs);
			$table      = new Associations();

			if ($table->load($conditions))
			{
				if (!$requested and !$table->delete())
				{
					return false;
				}

				continue;
			}

			if (!$requested)
			{
				continue;
			}

			if (!$table->save($conditions))
			{
				return false;
			}
		}

		return true;
	}
}
