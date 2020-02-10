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

use Organizer\Tables\Schedules as SchedulesTable;

/**
 * Provides general functions for schedule access checks, data retrieval and display.
 */
class Schedules extends ResourceHelper
{
	/**
	 * Returns the id of the active schedule for the given organization/term context
	 *
	 * @param   int  $organizationID  the id of the organization context
	 * @param   int  $termID          the id of the term context
	 *
	 * @return int the id of the active schedule for the context or 0
	 */
	public static function getActiveID($organizationID, $termID)
	{
		if (empty($organizationID) or empty($termID))
		{
			return 0;
		}

		$table = new SchedulesTable;

		return $table->load(['active' => 1, 'organizationID' => $organizationID, 'termID' => $termID]) ? $table->id : 0;
	}
}
