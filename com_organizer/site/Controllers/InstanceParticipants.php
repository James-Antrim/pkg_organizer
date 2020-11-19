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
	 * Class constructor
	 *
	 * @param   array  $config  An optional associative [] of configuration settings.
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->registerTask('add', 'add');
	}

	/**
	 * Adds instances to the user's personal schedule.
	 *
	 * @return void
	 */
	public function add()
	{
		$model = new Models\InstanceParticipant();

		$response = json_encode($model->add(), JSON_NUMERIC_CHECK);

		$this->jsonResponse($response);
	}

	/**
	 * Creates a new booking element for a given instance and redirects to the corresponding instance participants view.
	 *
	 * @return void
	 */
	public function createBooking ()
	{
		$model = new Models\InstanceParticipant();

		if (!$bookingID = $model->createBooking())
		{
			OrganizerHelper::message('ORGANIZER_BOOKING_CREATION_FAILED', 'notice');
			$this->setRedirect(Helpers\Input::getString('referrer'));
			return;
		}

		$url = Helpers\Routing::getRedirectBase() . "&view=instance_participants&id=$bookingID";
		$this->setRedirect(Route::_($url, false));
	}

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

	/**
	 * Removes instances to the user's personal schedule.
	 *
	 * @return void
	 */
	public function remove()
	{
		$model = new Models\InstanceParticipant();

		$response = json_encode($model->remove(), JSON_NUMERIC_CHECK);

		$this->jsonResponse($response);
	}
}