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
class Bookings extends Controller
{
	protected $listView = 'bookings';

	protected $resource = 'booking';

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
	 * Creates a new booking element for a given instance and redirects to the corresponding instance participants view.
	 *
	 * @return void
	 */
	public function add()
	{
		$model = new Models\Booking();

		if (!$bookingID = $model->add())
		{
			$this->setRedirect(Helpers\Input::getString('referrer'));

			return;
		}

		$url = Helpers\Routing::getRedirectBase() . "&view=booking&id=$bookingID";
		$this->setRedirect(Route::_($url, false));
	}

	/**
	 * Supplements the resource.
	 *
	 * @return void
	 */
	public function addParticipant()
	{
		$model = new Models\Booking();
		$model->addParticipant();
		$url = Helpers\Routing::getRedirectBase() . "&view=booking&id=" . Helpers\Input::getID();
		$this->setRedirect(Route::_($url, false));
	}

	/**
	 * Redirects to the manager from the form.
	 *
	 * @return void
	 */
	public function cancel()
	{
		$url = Helpers\Routing::getRedirectBase() . "&view=booking&id=" . Helpers\Input::getID();
		$this->setRedirect($url);
	}

	/**
	 * Closes a booking manually.
	 *
	 * @return void
	 */
	public function close()
	{
		$model = new Models\Booking();
		$model->close();
		$url = Helpers\Routing::getRedirectBase() . "&view=booking&id=" . Helpers\Input::getID();
		$this->setRedirect(Route::_($url, false));
	}

	/**
	 * Opens/reopens a booking manually.
	 *
	 * @return void
	 */
	public function open()
	{
		$model = new Models\Booking();
		$model->open();
		$url = Helpers\Routing::getRedirectBase() . "&view=booking&id=" . Helpers\Input::getID();
		$this->setRedirect(Route::_($url, false));
	}

	/**
	 * Removes the selected participants from the list of registered participants.
	 *
	 * @return void
	 */
	public function removeParticipants()
	{
		$model = new Models\Booking();
		$model->removeParticipants();
		$url = Helpers\Routing::getRedirectBase() . "&view=booking&id=" . Helpers\Input::getID();
		$this->setRedirect(Route::_($url, false));
	}

	/**
	 * Supplements the resource.
	 *
	 * @return void
	 */
	public function supplement()
	{
		$model = new Models\Booking();
		$model->supplement();
		$url = Helpers\Routing::getRedirectBase() . "&view=booking&id=" . Helpers\Input::getID();
		$this->setRedirect(Route::_($url, false));
	}

	/**
	 * Uses the model's upload function to validate and save the file to the database should validation be successful.
	 *
	 * @return void
	 */
	public function upload()
	{
		$url = Helpers\Routing::getRedirectBase();
		if (JDEBUG)
		{
			Helpers\OrganizerHelper::message('ORGANIZER_DEBUG_ON', 'error');
			$url .= "&view=Schedules";
			$this->setRedirect($url);

			return;
		}

		$form  = $this->input->files->get('jform', [], '[]');
		$file  = $form['file'];
		$types = [
			'application/csv',
			'application/vnd.ms-excel',
			'application/x-csv',
			'text/comma-separated-values',
			'text/csv',
			'text/plain',
			'text/tab-separated-values',
			'text/x-comma-separated-values',
			'text/x-csv'
		];

		$validType = (!empty($file['type']) and in_array($file['type'], $types));

		if ($validType and mb_detect_encoding($file['tmp_name'], 'UTF-8', true) === 'UTF-8')
		{
			$model = new Models\Booking();
			$model->upload();
		}

		$url = Helpers\Routing::getRedirectBase() . "&view=booking&id=" . Helpers\Input::getID();
		$this->setRedirect($url);
	}
}
