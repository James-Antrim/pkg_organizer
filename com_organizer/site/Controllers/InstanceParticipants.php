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
use Organizer\Helpers\Input;
use Organizer\Helpers\OrganizerHelper;
use Organizer\Models;

class InstanceParticipants extends Controller
{
	protected $listView = 'instance_participants';

	protected $resource = 'instance_participant';

	/**
	 * Sends an circular email to all course participants
	 *
	 * @return void
	 */
	public function notify()
	{
		$model = new Models\InstanceParticipant();

		if ($model->notify())
		{
			OrganizerHelper::message('ORGANIZER_NOTIFY_SUCCESS');
		}
		else
		{
			OrganizerHelper::message('ORGANIZER_NOTIFY_FAIL', 'error');
		}

		$url = Helpers\Routing::getRedirectBase() . '&view=course_participants&id=' . Input::getID();
		$this->setRedirect(Route::_($url, false));
	}
}