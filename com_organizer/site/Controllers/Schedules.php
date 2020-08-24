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
	 * Moves instances from the old table to the new table.
	 *
	 * @return void
	 */
	public function migrateResources()
	{
		$model = new Models\Schedule;

		if ($model->migrateResources())
		{
			Helpers\OrganizerHelper::message('Resources migrated.');
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
	public function migrateSchedules()
	{
		$model = new Models\Schedule;

		if ($model->migrateSchedules())
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
	 * Moves schedules from the old table to the new table.
	 *
	 * @return void
	 */
	public function moveSchedules()
	{
		$model = new Models\Schedule;

		if ($model->moveSchedules())
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
