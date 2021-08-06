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

class InstanceParticipants extends Controller
{
	private const ALL = 4, BLOCK = 2, SELECTED = 3, THIS = 1;

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
	 * Triggers the model to deregister the user for instances.
	 *
	 * @return void
	 */
	public function deregister(int $mode = self::BLOCK)
	{
		$model = new Models\InstanceParticipant();

		if ($model->deregister($mode))
		{
			$message = Languages::_('ORGANIZER_REGISTRATION_SUCCESS');
			$status  = 'message';
		}
		else
		{
			$message = Languages::_('ORGANIZER_REGISTRATION_FAIL');
			$status  = 'error';
		}

		OrganizerHelper::message($message, $status);
		$referrer = Helpers\Input::getInput()->server->getString('HTTP_REFERER');
		$this->setRedirect(Route::_($referrer, false));
	}

	/**
	 * Triggers the model to deregister all the instances of the same event & unit.
	 *
	 * @return void
	 */
	public function deregisterAll()
	{
		$this->deregister(self::ALL);
	}

	/**
	 * Triggers the model to deregister the instances of a single block for the same event & unit.
	 *
	 * @return void
	 */
	public function deregisterBlock()
	{
		$this->deregister(self::BLOCK);
	}

	/**
	 * Triggers the model to deregister the selected instances.
	 *
	 * @return void
	 */
	public function deregisterSelected()
	{
		$this->deregister(self::SELECTED);
	}

	/**
	 * Triggers the model to deregister a single instance.
	 *
	 * @return void
	 */
	public function deregisterThis()
	{
		$this->register(self::THIS);
	}

	/**
	 * Triggers the model to register the user for instances.
	 *
	 * @return void
	 */
	public function register(int $mode = self::BLOCK)
	{
		$model = new Models\InstanceParticipant();

		if ($model->register($mode))
		{
			$message = Languages::_('ORGANIZER_REGISTRATION_SUCCESS');
			$status  = 'message';
		}
		else
		{
			$message = Languages::_('ORGANIZER_REGISTRATION_FAIL');
			$status  = 'error';
		}

		OrganizerHelper::message($message, $status);
		$referrer = Helpers\Input::getInput()->server->getString('HTTP_REFERER');
		$this->setRedirect(Route::_($referrer, false));
	}

	/**
	 * Triggers the model to register all the instances of the same event & unit.
	 *
	 * @return void
	 */
	public function registerAll()
	{
		$this->register(self::ALL);
	}

	/**
	 * Triggers the model to register the instances of a single block for the same event & unit.
	 *
	 * @return void
	 */
	public function registerBlock()
	{
		$this->register(self::BLOCK);
	}

	/**
	 * Triggers the model to register the selected instances.
	 *
	 * @return void
	 */
	public function registerSelected()
	{
		$this->register(self::SELECTED);
	}

	/**
	 * Triggers the model to register a single instance.
	 *
	 * @return void
	 */
	public function registerThis()
	{
		$this->register(self::THIS);
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
}