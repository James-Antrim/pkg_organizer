<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored course data.
 */
class Course extends BaseModel
{
	const UNREGISTERED = null, WAITLIST = 0, REGISTERED = 1;

	/**
	 * Authorizes the user
	 */
	protected function allow()
	{
		return Helpers\Can::manage('course', Helpers\Input::getID());
	}

	/**
	 * Deregisters the user from the course.
	 *
	 * @return bool
	 */
	public function deregister()
	{
		$courseID      = Helpers\Input::getID();
		$participantID = Helpers\Users::getID();

		if (!$courseID or !$participantID)
		{
			Helpers\OrganizerHelper::message(Helpers\Languages::_('ORGANIZER_400'), 'error');

			return false;
		}

		if (!Helpers\Can::manage('participant', $participantID) or !Helpers\Can::manage('course', $courseID))
		{
			Helpers\OrganizerHelper::message(Helpers\Languages::_('ORGANIZER_403'), 'error');

			return false;
		}

		$dates = Helpers\Courses::getDates($courseID);

		if (empty($dates['endDate']) or $dates['endDate'] < date('Y-m-d'))
		{
			return false;
		}

		$courseParticipant = new Tables\CourseParticipants();
		$cpData            = ['courseID' => $courseID, 'participantID' => $participantID];

		if (!$courseParticipant->load($cpData) or !$courseParticipant->delete())
		{
			return false;
		}

		if ($instanceIDs = Helpers\Courses::getInstanceIDs($courseID))
		{
			foreach ($instanceIDs as $instanceID)
			{
				$ipData              = ['instanceID' => $instanceID, 'participantID' => $participantID];
				$instanceParticipant = new Tables\InstanceParticipants();
				if ($instanceParticipant->load($ipData))
				{
					$instanceParticipant->delete();
				}
			}
		}

		Helpers\Mailer::registrationUpdate($courseID, $participantID, null);

		return true;
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Courses A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Courses;
	}

	/**
	 * Registers the user for the course.
	 *
	 * @return bool
	 */
	public function register()
	{
		$courseID      = Helpers\Input::getID();
		$participantID = Helpers\Users::getID();
		$cpData        = ['courseID' => $courseID, 'participantID' => $participantID];

		$courseParticipant = new Tables\CourseParticipants();
		if (!$courseParticipant->load($cpData))
		{
			$cpData['participantDate'] = date('Y-m-d H:i:s');
			$cpData['status']          = self::REGISTERED;
			$cpData['statusDate']      = date('Y-m-d H:i:s');
			$cpData['attended']        = 0;
			$cpData['paid']            = 0;

			if (!$courseParticipant->save($cpData))
			{
				return false;
			}
		}

		if ($courseParticipant->status === self::REGISTERED)
		{
			if ($instanceIDs = Helpers\Courses::getInstanceIDs($courseID))
			{
				foreach ($instanceIDs as $instanceID)
				{
					$ipData              = ['instanceID' => $instanceID, 'participantID' => $participantID];
					$instanceParticipant = new Tables\InstanceParticipants();
					if (!$instanceParticipant->load($ipData))
					{
						$instanceParticipant->save($ipData);
					}
				}
			}
		}

		Helpers\Mailer::registrationUpdate($courseID, $participantID, $courseParticipant->status);

		return true;
	}

	/**
	 * Attempts to save the resource.
	 *
	 * @param   array  $data  the data from the form
	 *
	 * @return int|bool int id of the resource on success, otherwise boolean false
	 */
	public function save($data = [])
	{
		if (!$this->allow())
		{
			$referrer = Helpers\Input::getInput()->server->getString('HTTP_REFERER');
			Helpers\OrganizerHelper::message(Helpers\Languages::_('ORGANIZER_403'), 'error');
			Helpers\OrganizerHelper::getApplication()->redirect($referrer, 403);
		}

		$data  = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;
		$table = $this->getTable();

		if (empty($data['id']))
		{
			return $table->save($data) ? $table->id : false;
		}

		if (!$table->load($data['id']))
		{
			return false;
		}

		foreach ($data as $column => $value)
		{
			$table->$column = $value;
		}

		return $table->store() ? $table->id : false;
	}
}
