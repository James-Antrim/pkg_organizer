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
use Organizer\Helpers\InstanceParticipants as Helper;
use Organizer\Tables;
use Organizer\Tables\InstanceParticipants as Table;

/**
 * Class which manages stored course data.
 */
class InstanceParticipant extends BaseModel
{
	// Constants providing context for adding/removing instances to/from personal schedules.
	private const TERM_MODE = 1, BLOCK_MODE = 2, INSTANCE_MODE = 3;

	/**
	 * Adds instances to the user's personal schedule.
	 *
	 * @return array
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
	 * Authorizes the user.
	 *
	 * @return void
	 */
	protected function authorize()
	{
		$bookingID = 0;

		if (!$participationID = Helpers\Input::getID() or !$bookingID = Helper::getBookingID($participationID))
		{
			Helpers\OrganizerHelper::error(400);
		}

		if (!Helpers\Can::manage('booking', $bookingID))
		{
			Helpers\OrganizerHelper::error(403);
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
			Helpers\OrganizerHelper::message('ORGANIZER_401', 'error');

			return false;
		}

		$participant = new Participant();
		$participant->supplement($participantID);

		$data = Helpers\Input::getFormItems();
		if (!$code = $data->get('code') or !preg_match('/^[a-f0-9]{4}-[a-f0-9]{4}$/', $code))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_UNIT_CODE_INVALID', 'error');

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
			Helpers\OrganizerHelper::message('ORGANIZER_UNIT_CODE_INVALID', 'error');

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
				Helpers\OrganizerHelper::message(Helpers\Languages::_('ORGANIZER_CHECKIN_FAILED'));

				return false;
			}
		}

		Helpers\OrganizerHelper::message(Helpers\Languages::_('ORGANIZER_CHECKIN_SUCCEEDED'), 'success');

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
			Helpers\OrganizerHelper::message('ORGANIZER_401', 'error');

			return;
		}

		if (!$instanceID = Helpers\Input::getID())
		{
			Helpers\OrganizerHelper::message('ORGANIZER_400', 'error');

			return;
		}

		$instance = new Tables\Instances();
		if (!$instance->load($instanceID))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_412', 'error');

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
				Helpers\OrganizerHelper::message('ORGANIZER_EVENT_CONFIRMED', 'success');
			}
			else
			{
				Helpers\OrganizerHelper::message('ORGANIZER_412', 'error');
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
			Helpers\OrganizerHelper::message('ORGANIZER_401', 'error');

			return;
		}

		if (!$instanceID = Helpers\Input::getInt('instanceID') or !$roomID = Helpers\Input::getInt('roomID'))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_400', 'error');

			return;
		}

		$table = new Table();

		if (!$table->load(['instanceID' => $instanceID, 'participantID' => $participantID]))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_412', 'error');

			return;
		}

		$table->roomID = $roomID;
		$table->seat   = Helpers\Input::getString('seat');

		$table->store();
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
	 * Finds instances matching the given instance by matching method, inclusive the reference instance.
	 *
	 * @return array
	 */
	private function getMatchingInstances(): array
	{
		if (!$instanceID = Helpers\Input::getInt('instanceID'))
		{
			return [];
		}

		$matchingMethod = Helpers\Input::getInt('method', 2);
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
	 * Sends a circular mail to all course participants.
	 *
	 * @return bool true on success, false on error
	 */
	public function notify(): bool
	{
		return false;
		if (!$instanceID = Helpers\Input::getID())
		{
			return false;
		}

		if (!Helpers\Can::manageTheseOrganizations() and !Helpers\Instances::teaches($instanceID))
		{
			Helpers\OrganizerHelper::error(403);
		}

		$participants = Helpers\Instances::getParticipantIDs($instanceID);
		$selected     = Helpers\Input::getIntCollection('cids');

		if (empty($participants) and empty($selected))
		{
			return false;
		}

		$participantIDs = $selected ?: $participants;

		$form = Helpers\Input::getBatchItems();
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

		$data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;

		try
		{
			$table = $this->getTable();
		}
		catch (Exception $exception)
		{
			Helpers\OrganizerHelper::message($exception->getMessage(), 'error');

			return false;
		}

		if (!$table->load($data['id']))
		{
			return false;
		}

		$table->instanceID = $data['instanceID'];
		$table->roomID     = $data['roomID'];
		$table->seat       = $data['seat'];

		return $table->store() ? $table->id : false;
	}
}