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
use Organizer\Controller;
use Organizer\Helpers;
use Organizer\Models;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Schedules extends Controller
{
	protected $listView = 'schedules';

	protected $resource = 'schedule';

	/**
	 * Performs access checks. Checks if the schedule is already active. If the
	 * schedule is not already active, calls the activate function of the
	 * schedule model.
	 *
	 * @return void
	 */
	public function activate()
	{
		$model = new Models\Schedule;

		if ($model->activate())
		{
			Helpers\OrganizerHelper::message('ORGANIZER_ACTIVATE_SUCCESS');
		}
		else
		{
			Helpers\OrganizerHelper::message('ORGANIZER_ACTIVATE_FAIL', 'error');
		}

		$this->setRedirect("index.php?option=com_organizer&view={$this->listView}");
	}

	/**
	 * Moves schedules from the old table to the new table.
	 *
	 * @return void
	 */
	public function move()
	{
		$model = new Models\Schedule;

		if ($model->move())
		{
			Helpers\OrganizerHelper::message('Schedules moved.');
		}
		else
		{
			Helpers\OrganizerHelper::message('Failbot Activated!', 'error');
		}

		$url = Helpers\Routing::getRedirectBase();
		$url .= "&view=schedules";
		$this->setRedirect($url);
	}

	/**
	 * Moves schedules from the old table to the new table.
	 *
	 * @return void
	 */
	public function restructure()
	{
		$model = new Models\Schedule;

		if ($model->restructure())
		{
			Helpers\OrganizerHelper::message('Schedules restructured!');
		}
		else
		{
			Helpers\OrganizerHelper::message('Failbot Activated!', 'error');
		}

		$url = Helpers\Routing::getRedirectBase();
		$url .= "&view=schedules";
		$this->setRedirect($url);
	}

	/**
	 * performs access checks, activates/deactivates the chosen schedule in the
	 * context of its term, and redirects to the schedule manager view
	 *
	 * @return void
	 */
	public function setReference()
	{
		$model = new Models\Schedule;

		if ($model->setReference())
		{
			Helpers\OrganizerHelper::message('ORGANIZER_REFERENCE_SUCCESS');
		}
		else
		{
			Helpers\OrganizerHelper::message('ORGANIZER_REFERENCE_FAIL', 'error');
		}

		$url = Helpers\Routing::getRedirectBase();
		$url .= "&view=schedules";
		$this->setRedirect($url);
	}

	/**
	 * Performs access checks and uses the model's upload function to validate
	 * and save the file to the database should validation be successful
	 *
	 * @param   boolean  $shouldNotify  true if Upload and Notify button is pressed
	 *
	 * @return void
	 * @throws Exception
	 */
	public function upload($shouldNotify = false)
	{
		$url = Helpers\Routing::getRedirectBase();
		if (JDEBUG)
		{
			Helpers\OrganizerHelper::message('ORGANIZER_DEBUG_ON', 'error');
			$url .= "&view=Schedules";
			$this->setRedirect($url);

			return;
		}


		$form      = $this->input->files->get('jform', [], '[]');
		$file      = $form['file'];
		$validType = (!empty($file['type']) and $file['type'] == 'text/xml');

		if ($validType)
		{
			if (mb_detect_encoding($file['tmp_name'], 'UTF-8', true) === 'UTF-8')
			{
				$model = new Models\Schedule;
				$view  = $model->upload($shouldNotify) ? 'Schedules' : 'Schedule_Edit';
			}
			else
			{
				$view = 'Schedule_Edit';
				Helpers\OrganizerHelper::message('ORGANIZER_FILE_ENCODING_INVALID', 'error');
			}
		}
		else
		{
			$view = 'Schedule_Edit';
			Helpers\OrganizerHelper::message('ORGANIZER_FILE_TYPE_NOT_ALLOWED', 'error');
		}

		$url .= "&view={$view}";
		$this->setRedirect($url);
	}
}
