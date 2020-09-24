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
class Schedules extends Controller
{
	protected $listView = 'schedules';

	protected $resource = 'schedule';

	/**
	 * Notifies the points of contact for affected organizations of changes made to the schedule.
	 *
	 * @return void
	 */
	/*public function notify()
	{
		$model = new Models\Schedule();

		if ($model->notify())
		{
			Helpers\OrganizerHelper::message('ORGANIZER_NOTIFY_SUCCESS');
		}
		else
		{
			Helpers\OrganizerHelper::message('ORGANIZER_NOTIFY_FAIL', 'error');
		}

		$url = Helpers\Routing::getRedirectBase();
		$url .= "&view=schedules";
		$this->setRedirect($url);
	}*/

	/**
	 * Rebuilds the delta status of planning resources and relations.
	 *
	 * @return void
	 */
	public function rebuild()
	{
		$model = new Models\Schedule();

		if ($model->rebuild())
		{
			Helpers\OrganizerHelper::message('ORGANIZER_REBUILD_SUCCESS');
		}
		else
		{
			Helpers\OrganizerHelper::message('ORGANIZER_REBUILD_FAIL', 'error');
		}

		$url = Helpers\Routing::getRedirectBase();
		$url .= "&view=schedules";
		$this->setRedirect($url);
	}

	/**
	 * Uses the model's reference function to set the marked schedule as the reference in organization/term context.
	 *
	 * @return void
	 */
	public function reference()
	{
		$model = new Models\Schedule();

		if ($model->reference())
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

		$form      = $this->input->files->get('jform', [], '[]');
		$file      = $form['file'];
		$validType = (!empty($file['type']) and $file['type'] == 'text/xml');

		if ($validType)
		{
			if (mb_detect_encoding($file['tmp_name'], 'UTF-8', true) === 'UTF-8')
			{
				$model = new Models\Schedule;
				$view  = $model->upload() ? 'Schedules' : 'Schedule_Edit';
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
