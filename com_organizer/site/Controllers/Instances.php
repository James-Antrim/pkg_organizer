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
use Joomla\CMS\Router\Route;
use Organizer\Controller;
use Organizer\Helpers;
use Organizer\Models\Instance;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Instances extends Controller
{
	protected $listView = 'instances';

	protected $resource = 'instance';

	/**
	 * Ends the instance create/edit process and empties the session container.
	 *
	 * @return void
	 */
	public function cancel()
	{
		$session  = Factory::getSession();
		$instance = $session->get('organizer.instance', []);

		if (!empty($instance['referrer']))
		{
			Helpers\Input::set('referrer', $instance['referrer']);
		}

		$session->set('organizer.instance', '');

		parent::cancel();
	}

	/**
	 * Prints badges for the selected participants.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function gridA3()
	{
		Helpers\Input::set('format', 'pdf');
		Helpers\Input::set('layout', 'GridA3');
		parent::display();
	}

	/**
	 * Prints badges for the selected participants.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function gridA4()
	{
		Helpers\Input::set('format', 'pdf');
		Helpers\Input::set('layout', 'GridA4');
		parent::display();
	}

	/**
	 * Removed all properties stored in the session
	 *
	 * @return void
	 */
	public function reset()
	{
		$session  = Factory::getSession();
		$instance = $session->get('organizer.instance', []);

		if (!empty($instance['referrer']))
		{
			$instance = ['referrer' => $instance['referrer']];
		}

		$session->set('organizer.instance', $instance);

		parent::cancel();
	}

	/**
	 * Save form data to the database.
	 *
	 * @return void
	 */
	public function save()
	{
		$model    = new Instance();
		$session  = Factory::getSession();
		$instance = $session->get('organizer.instance', []);
		$referrer = empty($instance['referrer']) ? '' : $instance['referrer'];

		if ($model->save())
		{
			Helpers\OrganizerHelper::message('ORGANIZER_SAVE_SUCCESS', 'success');
			$session->set('organizer.instance', '');
			$this->setRedirect(Route::_($referrer, false));

			return;
		}

		Helpers\OrganizerHelper::message('ORGANIZER_SAVE_FAIL', 'error');

		$url = Helpers\Routing::getRedirectBase() . "&view=instance_edit";

		if ($id = Helpers\Input::getID())
		{
			$url .= "&id=$id";
		}

		if (Helpers\Input::getCMD('layout', 'appointment') === 'appointment')
		{
			$url .= '&appointment=1';
		}

		$this->setRedirect(Route::_($url, false));
	}
}
