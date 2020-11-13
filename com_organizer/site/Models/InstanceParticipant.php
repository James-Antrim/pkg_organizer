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
class InstanceParticipant extends BaseModel
{
	// Constants providing context for adding/removing instances to/from personal schedules.
	const TERM_MODE = 1, BLOCK_MODE = 2, INSTANCE_MODE = 3;

	/**
	 * Adds instances to the user's personal schedule.
	 *
	 * @return array
	 */
	public function add()
	{
		if (!$participantID = Helpers\Users::getID())
		{
			return [];
		}

		$participant = new Participant();
		$participant->supplement($participantID);

		$instanceIDs = $this->getMatchingInstances();

		foreach ($instanceIDs as $key => $instanceID)
		{
			$instanceParticipation = new Tables\InstanceParticipants();
			$keys                  = ['instanceID' => $instanceID, 'participantID' => $participantID];

			if ($instanceParticipation->load($keys))
			{
				continue;
			}

			if (!$instanceParticipation->save($keys))
			{
				unset($instanceIDs[$key]);
			}
		}

		return array_values($instanceIDs);
	}

	/**
	 * Checks the user into instances.
	 *
	 * @return void
	 */
	public function checkin()
	{
		if (!$participantID = Helpers\Users::getID())
		{
			Helpers\OrganizerHelper::message('ORGANIZER_401', 'error');

			return;
		}

		$participant = new Participant();
		$participant->supplement($participantID);

		$data = Helpers\Input::getFormItems();
		if (!$code = $data->get('code') or !preg_match('/\d{1,2}-\d{1,3}-\d+-\d{4}/', $code))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_400', 'error');

			return;
		}

		list($organizationID, $termID, $unitCode, $startTime) = explode('-', $code);
		$unit = new Tables\Units();
		if (!$unit->load(['code' => $unitCode, 'organizationID' => $organizationID, 'termID' => $termID]))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_UNIT_CODE_INVALID', 'error');

			return;
		}

		$today = date('Y-m-d');
		$time  = date('H:i:s', strtotime($startTime));
		$query = $this->_db->getQuery(true);
		$query->select('id')->from('#__organizer_blocks AS b')->where("b.date = '$today' AND startTime = '$time'");
		$this->_db->setQuery($query);

		if (!$blockIDs = Helpers\OrganizerHelper::executeQuery('loadColumn', []))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_UNIT_CODE_INVALID', 'error');

			return;
		}

		$blockIDs = implode(',', $blockIDs);
		$query    = $this->_db->getQuery(true);
		$query->select('id')
			->from('#__organizer_instances')
			->where("blockID IN ($blockIDs)")
			->where("unitID = $unit->id")
			->where('open = 1');
		$this->_db->setQuery($query);

		if (!$instanceIDs = Helpers\OrganizerHelper::executeQuery('loadColumn', []))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_UNIT_CODE_INVALID', 'error');

			return;
		}

		// Check for planned
		$query = $this->_db->getQuery(true);
		$query->select('id')
			->from('#__organizer_instance_participants')
			->where("instanceID IN (" . implode(',', $instanceIDs) . ")")
			->where("participantID = $participantID");
		$this->_db->setQuery($query);

		if ($plannedIDs = Helpers\OrganizerHelper::executeQuery('loadColumn', []))
		{
			$instanceIDs = array_intersect($plannedIDs, $instanceIDs);
		}

		foreach ($instanceIDs as $instanceID)
		{
			$data                  = ['instanceID' => $instanceID, 'participantID' => $participantID];
			$instanceParticipation = new Tables\InstanceParticipants();
			$instanceParticipation->load($data);
			$data['attended'] = 1;
			if (!$instanceParticipation->save($data))
			{
				Helpers\OrganizerHelper::message(Helpers\Languages::_('ORGANIZER_CHECKIN_FAILED'));
			}
		}
		Helpers\OrganizerHelper::message(Helpers\Languages::_('ORGANIZER_CHECKIN_SUCCEEDED'));
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
	 * Finds instances matching the given instance by matching method, inclusive the reference instance.
	 *
	 * @return array
	 */
	private function getMatchingInstances()
	{
		if (!$instanceID = Helpers\Input::getInt('instanceID'))
		{
			return [];
		}

		$matchingMethod = Helpers\Input::getInt('method', 2);

		$instance = new Tables\Instances();

		if (!$instance->load($instanceID))
		{
			return [];
		}

		$instanceIDs = [];
		$query       = $this->_db->getQuery(true);
		$query->select('i.id')
			->from('#__organizer_instances AS i')
			->where("i.eventID = {$instance->eventID}")
			->where("i.unitID = {$instance->unitID}")
			->order('i.id');

		// TODO only return instances that have not yet begun
		// TODO only return instances that are not full

		switch ($matchingMethod)
		{
			case self::BLOCK_MODE:
				$block = new Tables\Blocks();
				if ($block->load($instance->blockID))
				{
					$query->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
						->where("b.dow = {$block->dow}")
						->where("b.endTime = '{$block->endTime}'")
						->where("b.startTime = '{$block->startTime}'");
					$this->_db->setQuery($query);
					$instanceIDs = Helpers\OrganizerHelper::executeQuery('loadColumn', []);
				}
				break;
			case self::INSTANCE_MODE:
				$instanceIDs = [$instanceID];
				break;
			case self::TERM_MODE:
				$this->_db->setQuery($query);
				$instanceIDs = Helpers\OrganizerHelper::executeQuery('loadColumn', []);
				break;
			default:
				break;
		}

		return array_values($instanceIDs);
	}

	/**
	 * Sends a circular mail to all course participants.
	 *
	 * @return bool true on success, false on error
	 */
	public function notify()
	{
		return false;
		if (!$instanceID = Input::getID())
		{
			return false;
		}

		if (!Helpers\Can::manageTheseOrganizations() and !Helpers\Instances::teaches($instanceID))
		{
			Helpers\OrganizerHelper::error(403);
		}

		$instanceParticipants = Helpers\Instances::getParticipantIDs($instanceID);
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

	/**
	 * Removes instances from the user's personal schedule.
	 *
	 * @return array
	 */
	public function remove()
	{
		if (!$participantID = Helpers\Users::getID())
		{
			return [];
		}

		$instanceIDs = $this->getMatchingInstances();

		foreach ($instanceIDs as $key => $instanceID)
		{
			$instanceParticipation = new Tables\InstanceParticipants();
			$keys                  = ['instanceID' => $instanceID, 'participantID' => $participantID];

			if (!$instanceParticipation->load($keys) or !$instanceParticipation->delete())
			{
				unset($instanceIDs[$key]);
			}
		}

		return array_values($instanceIDs);
	}
}