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
	// Constants providing context for adding/removing instances to/from personal schedules though the interface.
	private const BLOCK = 2, SELECTED = 0, THIS = 1;

	/**
	 * Finds instances matching the given instance by course and date.
	 *
	 * @return void
	 */
	private function addCourseInstances(array &$instanceIDs)
	{
		$now             = date('H:i:s');
		$supplementalIDs = [];
		$today           = date('Y-m-d');
		$then            = date('Y-m-d', strtotime('+2 days'));

		foreach ($instanceIDs as $instanceID)
		{
			$instance = new Tables\Instances();
			$instance->load($instanceID);

			$query = Database::getQuery();
			$query->select('i1.id')
				->from('#__organizer_instances AS i1')
				->innerJoin('#__organizer_blocks AS b1 ON b1.id = i1.blockID')
				->innerJoin('#__organizer_instance_persons AS ip ON ip.instanceID = i1.id')
				->innerJoin('#__organizer_instance_rooms AS ir ON ir.assocID = ip.id')
				->innerJoin('#__organizer_rooms AS r ON r.id = ir.roomID')
				->innerJoin('#__organizer_units AS u1 ON u1.id = i1.unitID')
				->innerJoin('#__organizer_units AS u2 ON u2.courseID = u1.courseID')
				->innerJoin('#__organizer_instances AS i2 on i2.unitID = u2.id')
				->innerJoin('#__organizer_blocks AS b2 ON b2.id = i2.blockID')
				->where("i1.id != $instanceID")
				->where("r.virtual = 0")
				->where('u1.courseID IS NOT NULL')
				->where("i2.id = $instanceID")
				->where("(b1.startTime > b2.endTime or b1.endTime < b2.startTime)")
				->where("b1.date = b2.date")
				->where("(b1.date > '$today' OR (b1.date = '$today' and b1.startTime > '$now'))")
				->where("b1.date <= '$then'");

			Database::setQuery($query);
			$results = Database::loadIntColumn();

			$supplementalIDs = array_merge($supplementalIDs, $results);
		}

		$instanceIDs = array_merge($instanceIDs, $supplementalIDs);
		$instanceIDs = array_unique($instanceIDs);
		$instanceIDs = array_filter($instanceIDs);
	}

	/**
	 * Authorizes users responsible for bookings to edit individual participation.
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
	 * Adds instances to the user's personal schedule.
	 *
	 * @param   int  $method  the manner in which instances are selected.
	 *
	 * @return void
	 */
	public function bookmark(int $method)
	{
		if (!$participantID = Helpers\Users::getID())
		{
			OrganizerHelper::message(Languages::_('ORGANIZER_401'), 'error');

			return;
		}

		$participant = new Participant();
		$participant->supplement($participantID);

		if (!$instanceIDs = $this->getInstances($method, true))
		{
			return;
		}

		$bookmarked  = false;
		$responsible = false;

		foreach ($instanceIDs as $instanceID)
		{
			if (Helpers\Instances::hasResponsibility($instanceID))
			{
				$responsible = true;
				continue;
			}

			$participation = new Table();
			$keys          = ['instanceID' => $instanceID, 'participantID' => $participantID];

			// Participant already has the appointment bookmarked.
			if ($participation->load($keys))
			{
				continue;
			}

			if ($participation->save($keys))
			{
				$bookmarked = true;
				Helpers\Instances::updateNumbers($instanceID);
			}
		}

		if ($bookmarked)
		{
			OrganizerHelper::message('ORGANIZER_SCHEDULE_SUCCESS');
		}
		elseif ($responsible)
		{
			OrganizerHelper::message('ORGANIZER_INSTANCE_RESPONSIBLE_NOTICE', 'notice');
		}

		// The other option is that all matching instances were already in the participant's personal schedule => no message.
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

		// Filter for bookmarked/registered
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

			Helpers\Instances::updateNumbers($instanceID);
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

		foreach (Database::loadIntColumn() as $instanceID)
		{
			$participation = new Tables\InstanceParticipants();

			if ($participation->load(['instanceID' => $instanceID, 'participantID' => $participantID]))
			{
				$participation->delete();
				OrganizerHelper::message('ORGANIZER_EVENT_CONFIRMED', 'success');
				Helpers\Instances::updateNumbers($instanceID);
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

		$this->addCourseInstances($instanceIDs);

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
				Helpers\Instances::updateNumbers($instanceID);
			}
		}

		if ($deregistered)
		{
			OrganizerHelper::message(Languages::_('ORGANIZER_DEREGISTRATION_SUCCESS'));
		}

		// The other option is that the participant wasn't registered to any of the matching instances => no message.
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
	 * @param   int   $method   the method for determining relevant instances
	 * @param   bool  $virtual  whether virtual instances are permissible in the result set
	 *
	 * @return array
	 */
	private function getInstances(int $method, bool $virtual = false): array
	{
		$now   = date('H:i:s');
		$query = Database::getQuery();
		$today = date('Y-m-d');

		$query->select('i.id')
			->from('#__organizer_instances AS i')
			->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
			->innerJoin('#__organizer_instance_persons AS ip ON ip.instanceID = i.id')
			->innerJoin('#__organizer_instance_rooms AS ir ON ir.assocID = ip.id')
			->innerJoin('#__organizer_rooms AS r ON r.id = ir.roomID')
			->where("(b.date > '$today' OR (b.date = '$today' AND b.endTime > '$now'))")
			->order('i.id');

		if (!$virtual)
		{
			$query->where("r.virtual = 0");
		}

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

		$this->addCourseInstances($instanceIDs);

		$registered  = false;
		$responsible = false;

		foreach ($instanceIDs as $instanceID)
		{
			if (Helpers\Instances::hasResponsibility($instanceID))
			{
				$responsible = true;
				continue;
			}

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

			if (Helpers\Instances::getPresence($instanceID) === Helpers\Instances::ONLINE)
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

			$query = Database::getQuery();
			$query->select('i.id')
				->from('#__organizer_instance_participants AS ip')
				->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
				->where("i.id != $instanceID")
				->where("i.blockID = $block->id")
				->where('ip.registered = 1')
				->where("ip.participantID = $participantID");
			Database::setQuery($query);

			if ($otherInstanceID = Database::loadInt())
			{
				$otherName = Helpers\Instances::getName($otherInstanceID);
				OrganizerHelper::message(
					sprintf(Languages::_('ORGANIZER_INSTANCE_PREVIOUS_ENGAGEMENT'), $date, $startTime, $endTime,
						$otherName),
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
				Helpers\Instances::updateNumbers($instanceID);
			}
		}

		if ($registered)
		{
			OrganizerHelper::message(Languages::_('ORGANIZER_REGISTRATION_SUCCESS'));
		}
		elseif ($responsible)
		{
			OrganizerHelper::message('ORGANIZER_INSTANCE_RESPONSIBLE_NOTICE', 'notice');
		}

		// The other option is that the participant is already registered to all matching instances => no message.
	}

	/**
	 * Removes instances from a participant's personal schedule.
	 *
	 * @param   int  $method  the manner in which instances are filtered for removal.
	 *
	 * @return void
	 */
	public function removeBookmark(int $method)
	{
		if (!$participantID = Helpers\Users::getID())
		{
			OrganizerHelper::message(Languages::_('ORGANIZER_401'), 'error');

			return;
		}

		if (!$instanceIDs = $this->getInstances($method, true))
		{
			return;
		}

		$removed = false;

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
				$removed = true;
				Helpers\Instances::updateNumbers($instanceID);
			}
		}

		if ($removed)
		{
			OrganizerHelper::message(Languages::_('ORGANIZER_DESCHEDULE_SUCCESS'));
		}

		// The other option is that the participant didn't have matching instances in their personal schedule anyways => no message.
	}

	/**
	 * Attempts to save the resource.
	 *
	 * @param   array  $data  the data from the form
	 *
	 * @return bool int id of the resource on success, otherwise bool false
	 */
	public function save($data = []): bool
	{
		$this->authorize();

		$data = empty($data) ? Input::getFormItems()->toArray() : $data;

		$table = new Table();
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

		$instanceIDs = [];
		foreach (Database::loadAssocList() as $entry)
		{
			$instanceIDs[$entry['instanceID']] = $entry['instanceID'];
			$table->registered                 = $table->registered ?: !empty($entry['registered']);

			if ($entry['id'] !== $table->id)
			{
				$otherTable = new Table();

				if (!$otherTable->load($entry['id']))
				{
					continue;
				}

				$otherTable->delete();
			}
		}

		$success = $table->store();

		foreach ($instanceIDs as $instanceID)
		{
			Helpers\Instances::updateNumbers($instanceID);
		}

		return $success;
	}
}