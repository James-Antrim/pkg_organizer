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
class Fields extends Controller
{
	protected $listView = 'fields';

	protected $resource = 'field';

	/**
	 * Save form data to the database.
	 *
	 * @return void
	 */
	public function save()
	{
		$model = new Models\Field();
		$url   = Helpers\Routing::getRedirectBase() . '&view=';
		$url   .= Helpers\Can::administrate() ? 'fields' : 'field_colors';

		if ($model->save())
		{
			Helpers\OrganizerHelper::message('ORGANIZER_SAVE_SUCCESS', 'success');
		}
		else
		{
			Helpers\OrganizerHelper::message('ORGANIZER_SAVE_FAIL', 'error');
		}

		$this->setRedirect(Route::_($url, false));
	}
}
