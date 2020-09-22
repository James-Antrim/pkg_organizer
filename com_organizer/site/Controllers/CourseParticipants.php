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
use Joomla\CMS\Router\Route;
use Organizer\Controller;
use Organizer\Helpers;
use Organizer\Helpers\Input;
use Organizer\Helpers\OrganizerHelper;
use Organizer\Models;

class CourseParticipants extends Controller
{
	protected $listView = 'course_participants';

	protected $resource = 'course_participant';

	/**
	 * Accepts the selected participants into the course.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function accept()
	{
		$model = new Models\CourseParticipant();

		if ($model->accept())
		{
			OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_SUCCESS');
		}
		else
		{
			OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_FAIL', 'error');
		}

		$url = Helpers\Routing::getRedirectBase() . '&view=course_participants&id=' . Input::getID();
		$this->setRedirect(Route::_($url, false));
	}

	/**
	 * Changes the participant's course state.
	 *
	 * @return void
	 */
	public function changeState()
	{
		$model = new Models\CourseParticipant;

		if ($model->changeState())
		{
			OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_SUCCESS');
		}
		else
		{
			OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_FAIL', 'error');
		}

		$this->setRedirect(Input::getInput()->server->getString('HTTP_REFERER'));
	}

	/**
	 * Sends an circular email to all course participants
	 *
	 * @return void
	 * @throws Exception
	 */
	public function notify()
	{
		$model = new Models\CourseParticipant();

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
	 * Prints badges for the selected participants.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function printBadges()
	{
		// Reliance on POST requires a different method of redirection
		Helpers\Input::set('format', 'pdf');
		Helpers\Input::set('view', 'badges');
		parent::display();
	}

	/**
	 * Accepts the selected participants into the course.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function remove()
	{
		$model = new Models\CourseParticipant;

		if ($model->remove())
		{
			OrganizerHelper::message('ORGANIZER_REMOVE_SUCCESS');
		}
		else
		{
			OrganizerHelper::message('ORGANIZER_REMOVE_FAIL', 'error');
		}

		$url = Helpers\Routing::getRedirectBase() . '&view=course_participants&id=' . Input::getID();
		$this->setRedirect(Route::_($url, false));
	}

	/**
	 * Toggles binary resource properties from a list view.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function toggle()
	{
		$model = new Models\CourseParticipant;

		if ($model->toggle())
		{
			OrganizerHelper::message('ORGANIZER_TOGGLE_SUCCESS');
		}
		else
		{
			OrganizerHelper::message('ORGANIZER_TOGGLE_FAIL', 'error');
		}

		$url = Helpers\Routing::getRedirectBase() . '&view=course_participants&id=' . Input::getInt('courseID');
		$this->setRedirect(Route::_($url, false));
	}

	/**
	 * Accepts the selected participants into the course.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function waitlist()
	{
		$model = new Models\CourseParticipant();

		if ($model->waitlist())
		{
			OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_SUCCESS');
		}
		else
		{
			OrganizerHelper::message('ORGANIZER_STATUS_CHANGE_FAIL', 'error');
		}

		$url = Helpers\Routing::getRedirectBase() . '&view=course_participants&id=' . Input::getID();
		$this->setRedirect(Route::_($url, false));
	}
}