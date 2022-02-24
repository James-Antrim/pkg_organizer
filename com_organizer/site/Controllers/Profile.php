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

use Joomla\CMS\Router\Route;
use Organizer\Controller;
use Organizer\Helpers;
use Organizer\Models;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Profile extends Controller
{
	/**
	 * Creates a new booking element for a given instance and redirects to the corresponding instance participants view.
	 *
	 * @return void
	 */
	public function save()
	{
		$model = new Models\Profile();
		$model->save();

		$url = Helpers\Routing::getRedirectBase() . "&view=profile";
		$this->setRedirect(Route::_($url, false));
	}
}
