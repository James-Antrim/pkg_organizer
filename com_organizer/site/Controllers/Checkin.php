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
class Checkin extends Controller
{
	/**
	 * Checks the user into the
	 * @return void
	 */
	public function checkin()
	{
		$data = Helpers\Input::getFormItems();

		if (!$userID = Helpers\Users::getID())
		{
			$credentials = ['username' => $data->get('username'), 'password' => $data->get('password')];
			if (!Helpers\OrganizerHelper::getApplication()->login($credentials))
			{
				Helpers\OrganizerHelper::message('ORGANIZER_LOGIN_FAILED', 'error');
			}
		}

		$model = new Models\InstanceParticipant();
		$model->checkin();

		$url = Helpers\Routing::getRedirectBase() . "&view=checkin";
		$this->setRedirect($url);
	}

	/**
	 * Checks the user into the
	 * @return void
	 */
	public function confirm()
	{
		if ($userID = Helpers\Users::getID())
		{
			$model = new Models\InstanceParticipant();
			$model->confirm();
		}

		$url = Helpers\Routing::getRedirectBase() . "&view=checkin";
		$this->setRedirect($url);
	}
}
