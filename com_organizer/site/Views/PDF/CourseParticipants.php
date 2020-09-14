<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\PDF;

use Organizer\Helpers;
use Organizer\Tables;

/**
 * Provides methods relating to course participants for relevant PDF views.
 */
trait CourseParticipants
{
	/**
	 * Retrieves a list of relevant participants.
	 *
	 * @param   int  $courseID  the id of the course
	 *
	 * @return array the participants
	 */
	protected function getParticipants($courseID)
	{
		$allParticipants = Helpers\Courses::getParticipantIDs($courseID);
		if ($participantID = Helpers\Input::getInt('participantID'))
		{
			$selected = [$participantID];
		}
		else
		{
			$selected = Helpers\Input::getSelectedIDs();
		}

		// Participants were requested who are not registered to the course.
		if (array_diff($selected, $allParticipants))
		{
			return [];
		}

		$participantTemplate = [
			'address',
			'city',
			'forename',
			'id',
			'programID',
			'surname',
			'zipCode'
		];

		$selected     = $selected ? $selected : $allParticipants;
		$participants = [];
		foreach ($selected as $participantID)
		{
			$table = new Tables\Participants;
			if (!$table->load($participantID))
			{
				continue;
			}

			$participant = [];
			foreach ($participantTemplate as $property)
			{
				if (empty($table->$property))
				{
					unset($participants[$participantID]);
					continue 2;
				}

				$participant[$property] = $table->$property;

				if ($property === 'programID')
				{
					$participant['programName']      = Helpers\Programs::getName($table->$property);
					$organizationID                  = Helpers\Programs::getOrganization($table->$property);
					$participant['organizationName'] = Helpers\Organizations::getShortName($organizationID);
				}
			}

			$participants[] = $participant;
		}

		usort($participants, function ($participantOne, $participantTwo) {
			if ($participantOne['surname'] > $participantTwo['surname'])
			{
				return true;
			}

			if ($participantTwo['surname'] > $participantOne['surname'])
			{
				return false;
			}

			return $participantOne['forename'] > $participantTwo['forename'];
		});

		return $participants;
	}
}