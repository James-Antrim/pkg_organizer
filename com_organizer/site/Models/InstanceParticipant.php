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
use Organizer\Adapters\Database;
use Organizer\Helpers;
use Organizer\Helpers\Input;
use Organizer\Helpers\InstanceParticipants as Helper;
use Organizer\Helpers\Languages;
use Organizer\Helpers\OrganizerHelper;
use Organizer\Tables;
use Organizer\Tables\InstanceParticipants as Table;

/**
 * Class which manages stored course data.
 */
class InstanceParticipant extends BaseModel
{
	// Constants providing context for adding/removing instances to/from personal schedules though the old interface.
	private const TERM_MODE = 1, BLOCK_MODE = 2, INSTANCE_MODE = 3;

	// Constants providing context for adding/removing instances to/from personal schedules though the interface.
	private const BLOCK = 2, SELECTED = 0, THIS = 1;

	/**
	 * Adds instances to the user's personal schedule.
	 *
	 * @return array
	 * @see com_thm_organizer
	 */
	public function add(): array
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
			$participation = new Table();
			$keys          = ['instanceID' => $instanceID, 'participantID' => $participantID];

			if ($participation->load($keys))
			{
				continue;
			}

			if (!$participation->save($keys))
			{
				unset($instanceIDs[$key]);
			}
		}

		return array_values($instanceIDs);
	}

	/**
	 * Authorizes users responsible for bookings to edit individual participations.
	 *
	 * @return void
	 */
	protected function authorize()
	{
		$bookingID = 0;

		if (!$participationID = Input::getID() or !$bookingID = Helper::getBookingID($participationID))
		{
			OrganizerHelper::error(400);
		}

		if (!Helpers\Can::manage('booking', $bookingID))
		{
			OrganizerHelper::error(403);
		}
	}

	/**
	 * Checks the user into instances.
	 *
	 * @return bool true on success, otherwise false
	 */
	public function checkin(): bool
	{
		if (!$participantID = Helpers\Users::getID())
		{
			OrganizerHelper::message('ORGANIZER_401', 'error');

			return false;
		}

		$participant = new Participant();
		$participant->supplement($participantID);

		$data = Input::getFormItems();
		if (!$code = $data->get('code') or !preg_match('/^[a-f0-9]{4}-[a-f0-9]{4}$/', $code))
		{
			OrganizerHelper::message('ORGANIZER_UNIT_CODE_INVALID', 'error');

			return false;
		}

		$now   = date('H:i:s');
		$query = Database::getQuery();
		$then  = date('H:i:s', strtotime('+60 minutes'));
		$today = date('Y-m-d');
		$query->select('i.id')
			->from('#__organizer_instances AS i')
			->innerJoin('#__organizer_bookings AS bk ON bk.blockID = i.blockID AND bk.unitID = i.unitID')
			->where("bk.code = '$code'")
			->innerJoin('#__organizer_blocks AS bl ON bl.id = i.blockID')
			->where("bl.date = '$today'")
			->where("((bk.startTime IS NOT NULL and bk.startTime < '$then') or bl.startTime < '$then')")
			->where("bl.endTime > '$now'");
		Database::setQuery($query);

		if (!$instanceIDs = Database::loadIntColumn())
		{
			OrganizerHelper::message('ORGANIZER_UNIT_CODE_INVALID', 'error');

			return false;
		}

		// Filter for for planned
		$query = Database::getQuery();
		$query->select('instanceID')
			->from('#__organizer_instance_participants')
			->where("instanceID IN (" . implode(',', $instanceIDs) . ")")
			->where("participantID = $participantID");
		Database::setQuery($query);

		if ($plannedIDs = Database::loadIntColumn())
		{
			$instanceIDs = array_intersect($plannedIDs, $instanceIDs);
		}

		foreach ($instanceIDs as $instanceID)
		{
			$data = ['instanceID' => $instanceID, 'participantID' => $participantID];

			$participation = new Table();
			$participation->load($data);
			$data['attended'] = 1;

			if (!$participation->save($data))
			{
				OrganizerHelper::message(Languages::_('ORGANIZER_CHECKIN_FAILED'));

				return false;
			}
		}

		OrganizerHelper::message(Languages::_('ORGANIZER_CHECKIN_SUCCEEDED'), 'success');

		return true;
	}

	/**
	 * Resolves participant instance ambiguity.
	 *
	 * @return void
	 */
	public function confirmInstance()
	{
		if (!$participantID = Helpers\Users::getID())
		{
			OrganizerHelper::message('ORGANIZER_401', 'error');

			return;
		}

		if (!$instanceID = Input::getID())
		{
			OrganizerHelper::message('ORGANIZER_400', 'error');

			return;
		}

		$instance = new Tables\Instances();
		if (!$instance->load($instanceID))
		{
			OrganizerHelper::message('ORGANIZER_412', 'error');

			return;
		}

		// Get all other instances relevant to the booking
		$query = Database::getQuery();
		$query->select('id')
			->from('#__organizer_instances')
			->where("unitID = $instance->unitID")
			->where("blockID = $instance->blockID")
			->where("id != $instanceID");
		Database::setQuery($query);

		if ($instanceIDs = Database::loadIntColumn())
		{
			$instanceIDs = implode(',', $instanceIDs);
			$query       = Database::getQuery();
			$query->delete('#__organizer_instance_participants')
				->where("instanceID IN ($instanceIDs)")
				->where("participantID = $participantID");
			Database::setQuery($query);

			if (Database::execute())
			{
				OrganizerHelper::message('ORGANIZER_EVENT_CONFIRMED', 'success');
			}
			else
			{
				OrganizerHelper::message('ORGANIZER_412', 'error');
			}
		}
	}

	/**
	 * Confirms the participant's room and seat.
	 *
	 * @return void
	 */
	public function confirmSeating()
	{
		if (!$participantID = Helpers\Users::getID())
		{
			OrganizerHelper::message('ORGANIZER_401', 'error');

			return;
		}

		if (!$instanceID = Input::getInt('instanceID') or !$roomID = Input::getInt('roomID'))
		{
			OrganizerHelper::message('ORGANIZER_400', 'error');

			return;
		}

		$table = new Table();

		if (!$table->load(['instanceID' => $instanceID, 'participantID' => $participantID]))
		{
			OrganizerHelper::message('ORGANIZER_412', 'error');

			return;
		}

		$table->roomID = $roomID;
		$table->seat   = Input::getString('seat');

		$table->store();
	}

	/**
	 * Deregisters participants from instances.
	 *
	 * @param   int  $method  the method to be used for resolving the instances to be registered
	 *
	 * @return void
	 */
	public function deregister(int $method)
	{
		if (!$participantID = Helpers\Users::getID())
		{
			OrganizerHelper::message(Languages::_('ORGANIZER_401'), 'error');

			return;
		}

		// This filters out past instances.
		if (!$instanceIDs = $this->getInstances($method))
		{
			return;
		}

		$deregistered = false;

		foreach ($instanceIDs as $instanceID)
		{
			$participation = new Table();
			$keys          = ['instanceID' => $instanceID, 'participantID' => $participantID];

			// Participant was not registered to this instance.
			if (!$participation->load($keys) or !$participation->registered)
			{
				continue;
			}

			$keys['registered'] = false;

			if ($participation->save($keys))
			{
				$deregistered = true;
			}
		}

		if ($deregistered)
		{
			OrganizerHelper::message(Languages::_('ORGANIZER_DEREGISTRATION_SUCCESS'));
		}

		// The other option is that the participant wasn't registered to any of the matching instances => no message.
	}

	/**
	 * Removes instances from a participant's personal schedule.
	 *
	 * @param   int  $method  the manner in which instances are filtered for removal.
	 *
	 * @return void
	 */
	public function deschedule(int $method)
	{
		if (!$participantID = Helpers\Users::getID())
		{
			OrganizerHelper::message(Languages::_('ORGANIZER_401'), 'error');

			return;
		}

		if (!$instanceIDs = $this->getInstances($method))
		{
			return;
		}

		$descheduled = false;

		foreach ($instanceIDs as $instanceID)
		{
			$participation = new Table();
			$keys          = ['instanceID' => $instanceID, 'participantID' => $participantID];

			// The instance was not in the participant's personal schedule.
			if (!$participation->load($keys))
			{
				continue;
			}

			if ($participation->delete())
			{
				$descheduled = true;
			}
		}

		if ($descheduled)
		{
			OrganizerHelper::message(Languages::_('ORGANIZER_DESCHEDULE_SUCCESS'));
		}

		// The other option is that the participant didn't have matching instances in their personal schedule anyways => no message.
	}

	/**
	 * Finds instances matching the given instance by matching method, inclusive the reference instance.
	 *
	 * @return array
	 * @see com_thm_organizer
	 * @see self::add(), self::remove()
	 */
	private function getMatchingInstances(): array
	{
		if (!$instanceID = Input::getInt('instanceID'))
		{
			return [];
		}

		$matchingMethod = Input::getInt('method', 2);
		$instance       = new Tables\Instances();

		if (!$instance->load($instanceID))
		{
			return [];
		}

		$now   = date('H:i:s');
		$query = Database::getQuery();
		$today = date('Y-m-d');
		$query->select('i.id')
			->from('#__organizer_instances AS i')
			->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
			->where("i.eventID = $instance->eventID")
			->where("i.unitID = $instance->unitID")
			->where("(b.date > '$today' OR (b.date = '$today' AND b.endTime > '$now'))")
			->order('i.id');

		// TODO only return instances that are not full

		$instanceIDs = [];
		switch ($matchingMethod)
		{
			case self::BLOCK_MODE:
				$block = new Tables\Blocks();
				if ($block->load($instance->blockID))
				{
					$query->where("b.dow = $block->dow")
						->where("b.endTime = '$block->endTime'")
						->where("b.startTime = '$block->startTime'");
					Database::setQuery($query);
					$instanceIDs = Database::loadIntColumn();
				}
				break;
			case self::INSTANCE_MODE:
				$query->where("i.id = $instanceID");
				Database::setQuery($query);
				$instanceID  = Database::loadInt();
				$instanceIDs = $instanceID ? [$instanceID] : [];
				break;
			case self::TERM_MODE:
				Database::setQuery($query);
				$instanceIDs = Database::loadIntColumn();
				break;
			default:
				break;
		}

		return array_values($instanceIDs);
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return  Table  An instance participants table object
	 */
	public function getTable($name = '', $prefix = '', $options = []): Table
	{
		return new Table();
	}

	/**
	 * Finds instances matching the given instance by matching method, inclusive the reference instance. Adds system
	 * message if no results were found.
	 *
	 * @param   int  $method  the method for determining relevant instances
	 *
	 * @return array
	 */
	private function getInstances(int $method): array
	{
		$now   = date('H:i:s');
		$query = Database::getQuery();
		$today = date('Y-m-d');

		$query->select('i.id')
			->from('#__organizer_instances AS i')
			->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
			->where("(b.date > '$today' OR (b.date = '$today' AND b.endTime > '$now'))")
			->order('i.id');

		switch ($method)
		{
			// Called from instance item context, selected ids are not relevant
			case self::BLOCK:
				$block      = new Tables\Blocks();
				$instance   = new Tables\Instances();
				$instanceID = Input::getID();
				if (!$instanceID or !$instance->load($instanceID) or !$block->load($instance->blockID))
				{
					return [];
				}

				$query->where("i.eventID = $instance->eventID")
					->where("i.unitID = $instance->unitID")->where("b.dow = $block->dow")
					->where("b.endTime = '$block->endTime'")
					->where("b.startTime = '$block->startTime'");
				Database::setQuery($query);
				$instanceIDs = Database::loadIntColumn();
				break;

			// Called from instance item context, selected ids are not relevant
			case self::THIS:
				$instance   = new Tables\Instances();
				$instanceID = Input::getID();

				$instanceIDs = (!$instanceID or !$instance->load($instanceID)) ? [] : [$instanceID];
				break;

			// Called from instance_item or instances contexts
			case self::SELECTED:
			default:

				if (!$instanceIDs = Input::getSelectedIDs())
				{
					return [];
				}

				$selected = implode(',', $instanceIDs);
				$query->where("i.id IN ($selected)");
				Database::setQuery($query);
				$instanceIDs = Database::loadIntColumn();
				break;
		}

		if (!$instanceIDs = array_values($instanceIDs))
		{
			OrganizerHelper::message(Languages::_('ORGANIZER_NO_VALID_INSTANCES'), 'notice');
		}

		return $instanceIDs;
	}

	/**
	 * Sends a circular mail to all course participants.
	 *
	 * @return bool true on success, false on error
	 */
	public function notify(): bool
	{
		return false;
		/*if (!$instanceID = Input::getID())
		{
			return false;
		}

		if (!Helpers\Can::manageTheseOrganizations() and !Helpers\Instances::teaches($instanceID))
		{
			OrganizerHelper::error(403);
		}

		$participants = Helpers\Instances::getParticipantIDs($instanceID);
		$selected     = Input::getIntCollection('cid');

		if (empty($participants) and empty($selected))
		{
			return false;
		}

		$participantIDs = $selected ?: $participants;

		$form = Input::getBatchItems();
		if (!$subject = trim($form->get('subject', '')) or !$body = trim($form->get('body', '')))
		{
			return false;
		}

		foreach ($participantIDs as $participantID)
		{
			Helpers\Mailer::notifyParticipant($participantID, $subject, $body);
		}

		return true;*/
	}

	/**
	 * Registers participants to instances.
	 *
	 * @param   int  $method  the method to be used for resolving the instances to be registered
	 *
	 * @return void
	 */
	public function register(int $method)
	{
		if (!$participantID = Helpers\Users::getID())
		{
			OrganizerHelper::message(Languages::_('ORGANIZER_401'), 'error');

			return;
		}

		$participant = new Participant();
		$participant->supplement($participantID);

		// This filters out past instances.
		if (!$instanceIDs = $this->getInstances($method))
		{
			return;
		}

		$registered = false;

		foreach ($instanceIDs as $instanceID)
		{
			$participation = new Table();
			$keys          = ['instanceID' => $instanceID, 'participantID' => $participantID];

			// Participant is already registered.
			if ($participation->load($keys) and $participation->registered)
			{
				continue;
			}

			$name      = Helpers\Instances::getName($instanceID);
			$block     = Helpers\Instances::getBlock($instanceID);
			$date      = Helpers\Dates::formatDate($block->date);
			$earliest  = Helpers\Dates::formatDate(date('Y-m-d', strtotime('-2 days', strtotime($block->date))));
			$endTime   = Helpers\Dates::formatEndTime($block->endTime);
			$startTime = Helpers\Dates::formatTime($block->startTime);
			$then      = date('Y-m-d', strtotime('+2 days'));

			if (Helpers\Instances::isOnline($instanceID))
			{
				OrganizerHelper::message(
					sprintf(Languages::_('ORGANIZER_INSTANCE_ONLINE'), $name, $date, $startTime, $endTime),
					'notice'
				);
				continue;
			}

			if ($block->date > $then)
			{
				OrganizerHelper::message(
					sprintf(Languages::_('ORGANIZER_PREMATURE_REGISTRATION'), $name, $date, $startTime, $endTime,
						$earliest),
					'notice'
				);
				continue;
			}

			if (Helpers\Instances::isFull($instanceID))
			{
				OrganizerHelper::message(
					sprintf(Languages::_('ORGANIZER_INSTANCE_FULL_MESSAGE'), $name, $date, $startTime, $endTime),
					'notice'
				);
				continue;
			}

			$keys['registered'] = true;

			if ($participation->save($keys))
			{
				$registered = true;
			}
		}

		if ($registered)
		{
			OrganizerHelper::message(Languages::_('ORGANIZER_REGISTRATION_SUCCESS'));
		}

		// The other option is that the participant is already registered to all matching instances => no message.
	}

	/**
	 * Removes instances from the user's personal schedule.
	 *
	 * @return array
	 */
	public function remove(): array
	{
		if (!$participantID = Helpers\Users::getID())
		{
			return [];
		}

		$instanceIDs = $this->getMatchingInstances();

		foreach ($instanceIDs as $key => $instanceID)
		{
			$participation = new Table();
			$keys          = ['instanceID' => $instanceID, 'participantID' => $participantID];

			if (!$participation->load($keys) or !$participation->delete())
			{
				unset($instanceIDs[$key]);
			}
		}

		return array_values($instanceIDs);
	}

	/**
	 * Attempts to save the resource.
	 *
	 * @param   array  $data  the data from the form
	 *
	 * @return int|bool int id of the resource on success, otherwise bool false
	 */
	public function save($data = [])
	{
		$this->authorize();

		$data = empty($data) ? Input::getFormItems()->toArray() : $data;

		try
		{
			$table = new Table();
		}
		catch (Exception $exception)
		{
			OrganizerHelper::message($exception->getMessage(), 'error');

			return false;
		}

		if (!$table->load($data['id']))
		{
			return false;
		}

		$table->instanceID = $data['instanceID'];
		$table->roomID     = $data['roomID'];
		$table->seat       = $data['seat'];

		$query = Database::getQuery();
		$query->select('ip.*')
			->from('#__organizer_instance_participants AS ip')
			->innerJoin('#__organizer_instances AS i1 ON i1.id = ip.instanceID')
			->innerJoin('#__organizer_bookings AS b ON b.blockID = i1.blockID AND b.unitID = i1.unitID')
			->innerJoin('#__organizer_instances AS i2 ON i2.blockID = b.blockID AND i2.unitID = b.unitID')
			->where("i2.id = $table->instanceID")
			->where("ip.participantID = $table->participantID");
		Database::setQuery($query);

		$otherIDs = [];
		foreach (Database::loadAssocList() as $entry)
		{
			if ($entry['id'] !== $table->id)
			{
				$otherIDs[$entry['id']] = $entry['id'];
			}

			$table->registered = $table->registered ?: !empty($entry['registered']);
		}

		// The other entries must first be deleted to avoid collision with unique instanceID/participantID constraint.
		foreach ($otherIDs as $otherID)
		{
			$otherTable = new Table();

			if (!$otherTable->load($otherID))
			{
				continue;
			}

			$otherTable->delete();
		}

		return $table->store();
	}

	/**
	 * Adds instances to the user's personal schedule.
	 *
	 * @param   int  $method  the manner in which instances are selected.
	 *
	 * @return void
	 */
	public function schedule(int $method)
	{
		if (!$participantID = Helpers\Users::getID())
		{
			OrganizerHelper::message(Languages::_('ORGANIZER_401'), 'error');

			return;
		}

		$participant = new Participant();
		$participant->supplement($participantID);

		if (!$instanceIDs = $this->getInstances($method))
		{
			return;
		}

		$registered = false;

		foreach ($instanceIDs as $instanceID)
		{
			$participation = new Table();
			$keys          = ['instanceID' => $instanceID, 'participantID' => $participantID];

			// Participant is already registered.
			if ($participation->load($keys))
			{
				continue;
			}

			if ($participation->save($keys))
			{
				$registered = true;
			}
		}

		if ($registered)
		{
			OrganizerHelper::message('ORGANIZER_SCHEDULE_SUCCESS');
		}

		// The other option is that all matching instances were already in the participant's personal schedule => no message.
	}
}