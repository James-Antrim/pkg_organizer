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
	 * Checks access to edit the resource.
	 *
	 * @return void
	 */
	public function authorize()
	{
		if (!Helpers\Users::getUser())
		{
			Helpers\OrganizerHelper::error(401);
		}

		// This isn't specific because it is only a pseudo-edit view (upload)
		if (!Helpers\Can::scheduleTheseOrganizations())
		{
			Helpers\OrganizerHelper::error(403);
		}
	}
}
