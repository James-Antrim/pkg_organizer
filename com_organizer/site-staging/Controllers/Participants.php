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
class Participants extends Controller
{
	const UNREGISTERED = null;

	use CourseParticipants;

	protected $listView = 'participants';

	protected $resource = 'participant';

	/**
	 * Save user information from form and if course id defined sign in or out of course
	 * then redirect to course list view
	 *
	 * @return void
	 * @throws Exception
	 */
	public function save()
	{
		$model = new Models\Participant;

		if ($participantID = $model->save())
		{
			Helpers\OrganizerHelper::message('ORGANIZER_SAVE_SUCCESS', 'success');
		}
		else
		{
			Helpers\OrganizerHelper::message('ORGANIZER_SAVE_FAIL', 'error');
		}

		$this->setRedirect(Helpers\Input::getString('referrer'));
	}

	/**
	 * Redirects to the referring view.
	 *
	 * @return void
	 */
	public function cancel()
	{
		$this->setRedirect(Helpers\Input::getString('referrer'));
	}
}
