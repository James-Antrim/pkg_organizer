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
use Joomla\CMS\Uri\Uri;
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
	 * Prints badges for the selected participants.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function badge()
	{
		Helpers\Input::set('format', 'pdf');
		Helpers\Input::set('layout', 'Badge');
		parent::display();
	}

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
			Helpers\OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_SUCCESS', 'success');
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
		if (!$courseID = Helpers\Input::getSelectedIDs()[0])
		{
			parent::display();

			return;
		}

		$URL = Uri::base() . "?option=com_organizer&view=course_participants&id=$courseID";

		if ($tag = Helpers\Input::getCMD('languageTag'))
		{
			$URL .= "&languageTag=$tag";
		}

		$this->setRedirect($URL);
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
			Helpers\OrganizerHelper::message('ORGANIZER_PROFILE_INCOMPLETE_ERROR', 'error');
		}
		else
		{
			$model = new Models\Course();

			if ($model->register())
			{
				Helpers\OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_SUCCESS', 'success');
			}
			else
			{
				Helpers\OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_FAIL', 'error');
			}
		}


		$this->setRedirect($referrer);
	}
}
