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
use Organizer\Helpers\Input;
use Organizer\Tables;

/**
 * Class which manages stored course data.
 */
class InstanceParticipant extends BaseModel
{
	const ACCEPTED = 1, ATTENDED = 1, PAID = 1, WAITLIST = 0;

	/**
	 * Sets the property the given property to the given value for the selected participants.
	 *
	 * @param   string  $property  the property to update
	 * @param   int     $value     the new value for the property
	 *
	 * @return bool true on success, otherwise false
	 */
	private function batch($property, $value)
	{
		if (!$courseID = Input::getID() or !$participantIDs = Input::getSelectedIDs())
		{
			return false;
		}

		if (!Helpers\Can::manage('course', $courseID))
		{
			Helpers\OrganizerHelper::error(403);
		}

		foreach ($participantIDs as $participantID)
		{
			if (!Helpers\Can::manage('participant', $participantID))
			{
				Helpers\OrganizerHelper::error(403);
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
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\InstanceParticipants A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\InstanceParticipants();
	}

	/**
	 * Sends a circular mail to all course participants.
	 *
	 * @return bool true on success, false on error
	 */
	public function notify()
	{
		if (!$instanceID = Input::getID())
		{
			return false;
		}

		if (!Helpers\Can::manageTheseOrganizations() and !Helpers\Instances::teaches($instanceID))
		{
			Helpers\OrganizerHelper::error(403);
		}

		$instanceParticipants   = Helpers\Instances::getParticipantIDs($instanceID);
		$selectedParticipants = Input::getInput()->get('cids', [], 'array');

		if (empty($instanceParticipants) and empty($selectedParticipants))
		{
			return false;
		}

		$participantIDs = $selectedParticipants ? $selectedParticipants : $instanceParticipants;

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
}