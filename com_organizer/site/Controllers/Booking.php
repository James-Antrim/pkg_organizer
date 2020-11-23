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
class Booking extends Controller
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
			Helpers\OrganizerHelper::message('ORGANIZER_BOOKING_CREATION_FAILED', 'notice');
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
	public function supplement()
	{
		$model = new Models\Booking();

		if (!$model->supplement())
		{
			Helpers\OrganizerHelper::message('ORGANIZER_SAVE_FAIL', 'error');
		}
		else
		{
			Helpers\OrganizerHelper::message('ORGANIZER_SAVE_SUCCESS');
		}

		$url = Helpers\Routing::getRedirectBase() . "&view=booking&id=" . Helpers\Input::getID();
		$this->setRedirect(Route::_($url, false));
	}
}
