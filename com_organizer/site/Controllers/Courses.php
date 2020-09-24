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
class Courses extends Controller
{
	const UNREGISTERED = null;

	protected $listView = 'courses';

	protected $resource = 'course';

	/**
	 * De-/registers a participant from/to a course.
	 *
	 * @return void
	 */
	public function deregister()
	{
		$referrer = Helpers\Input::getInput()->server->getString('HTTP_REFERER');

		$model = new Models\Course();

		if ($model->deregister())
		{
			Helpers\OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_SUCCESS');
		}
		else
		{
			Helpers\OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_FAIL', 'error');
		}

		$this->setRedirect($referrer);
	}

	/**
	 * Opens the course participants view for the selected course.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function participants()
	{
		// Reliance on POST requires a different method of redirection
		Helpers\Input::set('id', Helpers\Input::getSelectedIDs()[0]);
		Helpers\Input::set('view', 'course_participants');
		parent::display();
	}

	/**
	 * De-/registers a participant from/to a course.
	 *
	 * @return void
	 */
	public function register()
	{
		$courseID      = Helpers\Input::getID();
		$referrer      = Helpers\Input::getInput()->server->getString('HTTP_REFERER');
		$participantID = Helpers\Users::getID();

		if (!Helpers\CourseParticipants::validProfile($courseID, $participantID))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_PROFILE_INCOMPLETE', 'error');
		}
		else
		{
			$model = new Models\Course();

			if ($model->register())
			{
				Helpers\OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_SUCCESS');
			}
			else
			{
				Helpers\OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_FAIL', 'error');
			}
		}


		$this->setRedirect($referrer);
	}
}
