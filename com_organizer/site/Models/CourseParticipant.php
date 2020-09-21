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

use Exception;
use Organizer\Helpers;
use Organizer\Helpers\Input;
use Organizer\Helpers\Languages;
use Organizer\Tables;

/**
 * Class which manages stored course data.
 */
class CourseParticipant extends BaseModel
{
	const ACCEPTED = 1, ATTENDED = 1, PAID = 1, WAITLIST = 0;

	/**
	 * Sets the status for the course participant to accepted
	 *
	 * @return bool true on success, otherwise false
	 * @throws Exception invalid / unauthorized access
	 */
	public function accept()
	{
		return $this->batch('status', self::ACCEPTED);
	}

	/**
	 * Sets the property the given property to the given value for the selected participants.
	 *
	 * @param   string  $property  the property to update
	 * @param   int     $value     the new value for the property
	 *
	 * @return bool true on success, otherwise false
	 * @throws Exception invalid / unauthorized access
	 */
	private function batch($property, $value)
	{
		if (!$courseID = Input::getID() or !$participantIDs = Input::getSelectedIDs())
		{
			throw new Exception(Languages::_('ORGANIZER_400'), 400);
		}

		if (!Helpers\Can::manage('course', $courseID))
		{
			throw new Exception(Languages::_('ORGANIZER_403'), 403);
		}

		foreach ($participantIDs as $participantID)
		{
			if (!Helpers\Can::manage('participant', $participantID))
			{
				throw new Exception(Languages::_('ORGANIZER_403'), 403);
			}

			$table = $this->getTable();

			if (!$table->load(['courseID' => $courseID, 'participantID' => $participantID]))
			{
				return false;
			}

			if ($table->$property === $value)
			{
				continue;
			}

			$table->$property = $value;

			if (!$table->store())
			{
				return false;
			}

			if ($property === 'status')
			{
				Helpers\Mailer::registrationUpdate($courseID, $participantID, $value);
			}
		}

		return true;
	}

	/**
	 * Saves data for participants when administrator changes state in manager
	 *
	 * @return bool true on success, false on error
	 * @throws Exception => unauthorized access
	 */
	public function changeParticipantState()
	{
		$data     = Input::getInput()->getArray();
		$courseID = Input::getID();

		if (!Helpers\Can::manage('course', $courseID))
		{
			throw new Exception(Languages::_('ORGANIZER_403'), 403);
		}

		$participantIDs = $data['checked'];
		$state          = (int) $data['participantState'];
		$invalidState   = ($state < 0 or $state > 2);

		if (empty($participantIDs) or empty($courseID) or $invalidState)
		{
			return false;
		}

		foreach ($data['checked'] as $participantID)
		{
			if (!Participants::changeState($participantID, $courseID, $state))
			{
				return false;
			}

			if ($state === 0)
			{
				Helpers\Courses::refreshWaitList($courseID);
			}
		}

		return true;
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\CourseParticipants A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\CourseParticipants;
	}

	/**
	 * Sends a circular mail to all course participants.
	 *
	 * @return bool true on success, false on error
	 * @throws Exception => invalid / unauthorized access
	 */
	public function notify()
	{
		if (!$courseID = Input::getID())
		{
			throw new Exception(Languages::_('ORGANIZER_400'), 400);
		}
		elseif (!Helpers\Can::manage('course', $courseID))
		{
			throw new Exception(Languages::_('ORGANIZER_403'), 403);
		}

		$courseParticipants   = Helpers\Courses::getParticipantIDs($courseID);
		$selectedParticipants = Input::getInput()->get('cids', [], 'array');

		if (empty($courseParticipants) and empty($selectedParticipants))
		{
			return false;
		}

		$participantIDs = $selectedParticipants ? $selectedParticipants : $courseParticipants;

		$form = Input::getBatchItems();
		if (!$subject = trim($form->get('subject', '')) or !$body = trim($form->get('body', '')))
		{
			return false;
		}

		foreach ($participantIDs as $participantID)
		{
			Helpers\Mailer::notifyParticipant($participantID, $subject, $body);
		}

		return true;
	}

	/**
	 * Sets the payment status to paid.
	 *
	 * @return bool true on success, otherwise false
	 * @throws Exception invalid / unauthorized access
	 */
	public function remove()
	{
		if (!$courseID = Input::getID() or !$participantIDs = Input::getSelectedIDs())
		{
			Helpers\OrganizerHelper::message(Languages::_('ORGANIZER_400'), 'error');

			return false;
		}

		if (!Helpers\Can::manage('course', $courseID))
		{
			Helpers\OrganizerHelper::message(Languages::_('ORGANIZER_403'), 'error');

			return false;
		}

		$dates = Helpers\Courses::getDates($courseID);

		if (empty($dates['endDate']) or $dates['endDate'] < date('Y-m-d'))
		{
			return false;
		}

		$instanceIDs = Helpers\Courses::getInstanceIDs($courseID);
		$instanceIDs = implode(',', $instanceIDs);

		foreach ($participantIDs as $participantID)
		{
			if (!Helpers\Can::manage('participant', $participantID))
			{
				Helpers\OrganizerHelper::message(Languages::_('ORGANIZER_403'), 'error');

				return false;
			}

			$courseParticipant = new Tables\CourseParticipants();
			$cpData            = ['courseID' => $courseID, 'participantID' => $participantID];

			if (!$courseParticipant->load($cpData) or !$courseParticipant->delete())
			{
				return false;
			}

			$participantIDs = implode(',', $participantIDs);
			$query          = $this->_db->getQuery('true');
			$query->delete('#__organizer_instance_participants')
				->where("instanceID IN ($instanceIDs)")
				->where("participantID = $participantIDs");
			$this->_db->setQuery($query);

			if (!Helpers\OrganizerHelper::executeQuery('execute'))
			{
				return false;
			}

			Helpers\Mailer::registrationUpdate($courseID, $participantID, null);
		}

		return true;
	}

	/**
	 * Toggles binary attributes of the course participant association.
	 *
	 * @return bool true on success, otherwise false
	 * @throws Exception invalid / unauthorized access
	 */
	public function toggle()
	{
		$attribute     = Input::getCMD('attribute', '');
		$courseID      = Input::getInt('courseID', 0);
		$participantID = Input::getInt('participantID', 0);
		if (!$attribute or !$courseID or !$participantID)
		{
			throw new Exception(Languages::_('ORGANIZER_400'), 400);
		}

		if (!Helpers\Can::manage('course', $courseID) or !Helpers\Can::manage('participant', $participantID))
		{
			throw new Exception(Languages::_('ORGANIZER_403'), 403);
		}

		$table = $this->getTable();
		if (!property_exists($table, $attribute))
		{
			return false;
		}

		if (!$table->load(['courseID' => $courseID, 'participantID' => $participantID]))
		{
			return false;
		}

		$table->$attribute = !$table->$attribute;

		if (!$table->store())
		{
			return false;
		}

		if ($attribute === 'status')
		{
			Helpers\Mailer::registrationUpdate($courseID, $participantID, $table->$attribute);
		}

		return true;
	}

	/**
	 * Sets the status for the course participant to accepted
	 *
	 * @return bool true on success, otherwise false
	 * @throws Exception invalid / unauthorized access
	 */
	public function waitlist()
	{
		return $this->batch('status', self::WAITLIST);
	}
}