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
use Joomla\CMS\Factory;
use Organizer\Controller;
use Organizer\Helpers;
use Organizer\Models;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Checkin extends Controller
{
	/**
	 * Checks the user into a booking.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function checkin()
	{
		$data    = Helpers\Input::getFormItems();
		$session = Factory::getSession();

		if (!Helpers\Users::getID())
		{
			$credentials = ['username' => $data->get('username'), 'password' => $data->get('password')];
			$username    = Helpers\OrganizerHelper::getApplication()->login($credentials) ? '' : $data->get('username');
			$session->set('organizer.checkin.username', $username);
		}

		if (Helpers\Users::getID())
		{
			$model = new Models\InstanceParticipant();
			$code  = $model->checkin() ? '' : $data->get('code');
			$session->set('organizer.checkin.code', $code);
		}
		else
		{
			$session->set('organizer.checkin.code', $data->get('code'));
		}

		$url = Helpers\Routing::getRedirectBase() . "&view=checkin";
		$this->setRedirect($url);
	}

	/**
	 * Resolves participant instance ambiguity.
	 *
	 * @return void
	 */
	public function confirmInstance()
	{
		if (Helpers\Users::getID())
		{
			$model = new Models\InstanceParticipant();
			$model->confirmInstance();
		}

		$url = Helpers\Routing::getRedirectBase() . "&view=checkin";
		$this->setRedirect($url);
	}

	/**
	 * Confirms the participant's room and seat.
	 *
	 * @return void
	 */
	public function confirmSeating()
	{
		if (Helpers\Users::getID())
		{
			$model = new Models\InstanceParticipant();
			$model->confirmSeating();
		}

		$url = Helpers\Routing::getRedirectBase() . "&view=checkin";
		$this->setRedirect($url);
	}

	/**
	 * Saves the participants contact data.
	 *
	 * @return void
	 */
	public function contact()
	{
		if (Helpers\Users::getID())
		{
			$model = new Models\Participant();
			$model->save();
		}

		$url = Helpers\Routing::getRedirectBase() . "&view=checkin";
		$this->setRedirect($url);
	}
}
