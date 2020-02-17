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

use Exception;
use Organizer\Controller;
use Organizer\Helpers\OrganizerHelper;
use Organizer\Helpers\Routing;
use Organizer\Models;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Programs extends Controller
{
	protected $listView = 'programs';

	protected $resource = 'program';

	/**
	 * Makes call to the models's update batch function, and redirects to the manager view.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function update()
	{
		$model = new Models\Program;

		if ($model->update())
		{
			OrganizerHelper::message('ORGANIZER_UPDATE_SUCCESS');
		}
		else
		{
			OrganizerHelper::message('ORGANIZER_UPDATE_FAIL', 'error');
		}

		$url = Routing::getRedirectBase();
		$url .= "&view={$this->listView}";
		$this->setRedirect($url);
	}
}
