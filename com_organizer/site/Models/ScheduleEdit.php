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

/**
 * Class loads a form for uploading schedule data.
 */
class ScheduleEdit extends EditModel
{
	/**
	 * Checks for user authorization to access the view.
	 *
	 * @return bool  true if the user can access the edit view, otherwise false
	 */
	public function allow()
	{
		return (bool) Helpers\Can::scheduleTheseOrganizations();
	}
}
