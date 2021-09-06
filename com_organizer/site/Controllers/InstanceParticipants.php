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

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Organizer\Controller;
use Organizer\Helpers;
use Organizer\Helpers\Languages;
use Organizer\Helpers\OrganizerHelper;
use Organizer\Models;

/**
 * Class provides methods for participant interation with instances.
 */
class InstanceParticipants extends Controller
{
	private const BLOCK = 2, SELECTED = 0, THIS = 1;

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
	 * Adds instances to the participant's personal schedule.
	 *
	 * @return void
	 * @see com_thm_organizer
	 */
	public function add()
	{
		$model = new Models\InstanceParticipant();

		$response = json_encode($model->add(), JSON_NUMERIC_CHECK);

		$this->jsonResponse($response);
	}

	/**
	 * Triggers the model to deregister the participant from instances.
	 *
	 * @return void
	 */
	public function deregister()
	{
		$model = new Models\InstanceParticipant();
		$model->deregister();
		$referrer = Helpers\Input::getInput()->server->getString('HTTP_REFERER');
		$this->setRedirect(Route::_($referrer, false));
	}

	/**
	 * Triggers the model to remove instances from the participant's personal schedule.
	 *
	 * @return void
	 */
	public function deschedule(int $method = self::SELECTED)
	{
		$model = new Models\InstanceParticipant();
		$model->deschedule($method);
		$referrer = Helpers\Input::getInput()->server->getString('HTTP_REFERER');
		$this->setRedirect(Route::_($referrer, false));
	}

	/**
	 * Triggers the model to remove instances of a unique block (dow/times) and event tuple from the participant's personal schedule.
	 *
	 * @return void
	 */
	public function descheduleBlock()
	{
		$this->deschedule(self::BLOCK);
	}

	/**
	 * Triggers the model to register the participant to instances.
	 *
	 * @return void
	 */
	public function register()
	{
		$model = new Models\InstanceParticipant();
		$model->register();
		$referrer = Helpers\Input::getInput()->server->getString('HTTP_REFERER');
		$this->setRedirect(Route::_($referrer, false));
	}

	/**
	 * Removes instances to the participant's personal schedule.
	 *
	 * @return void
	 * @see com_thm_organizer
	 */
	public function remove()
	{
		$model = new Models\InstanceParticipant();

		$response = json_encode($model->remove(), JSON_NUMERIC_CHECK);

		$this->jsonResponse($response);
	}

	/**
	 * Save form data to the database.
	 *
	 * @return void
	 */
	public function save()
	{
		$model = new Models\InstanceParticipant();

		if ($model->save())
		{
			OrganizerHelper::message('ORGANIZER_SAVE_SUCCESS', 'success');
			Factory::getSession()->set('organizer.participation.referrer', '');
			$referrer = Helpers\Input::getString('referrer');
			$this->setRedirect(Route::_($referrer, false));
		}
		else
		{
			OrganizerHelper::message('ORGANIZER_SAVE_FAIL', 'error');
		}
	}

	/**
	 * Triggers the model to add instances to the participant's personal schedule.
	 *
	 * @return void
	 */
	public function schedule(int $method = self::SELECTED)
	{
		$model = new Models\InstanceParticipant();
		$model->schedule($method);
		$referrer = Helpers\Input::getInput()->server->getString('HTTP_REFERER');
		$this->setRedirect(Route::_($referrer, false));
	}

	/**
	 * Triggers the model to add instances of a unique block (dow/times) and event to a participant's personal schedule.
	 *
	 * @return void
	 */
	public function scheduleBlock()
	{
		$this->schedule(self::BLOCK);
	}
}