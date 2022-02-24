<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Controllers;

use Organizer\Controller;
use Organizer\Helpers;
use Organizer\Models;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Programs extends Controller
{
	use Activated, Imported;

	protected $listView = 'programs';

	protected $resource = 'program';

	/**
	 * Makes call to the models's update batch function, and redirects to the manager view.
	 *
	 * @return void
	 */
	public function update()
	{
		$model = new Models\Program();

		if ($model->update())
		{
			Helpers\OrganizerHelper::message('ORGANIZER_UPDATE_SUCCESS', 'success');
		}
		else
		{
			Helpers\OrganizerHelper::message('ORGANIZER_UPDATE_FAIL', 'error');
		}

		$url = Helpers\Routing::getRedirectBase();
		$url .= "&view={$this->listView}";
		$this->setRedirect($url);
	}
}
