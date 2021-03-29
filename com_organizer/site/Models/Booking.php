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

use Joomla\CMS\Form\Form;
use Joomla\CMS\User\User;
use Organizer\Adapters\Database;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class retrieves information for a filtered set of participants.
 */
class Booking extends Participants
{
	protected $defaultOrdering = 'fullName';

	protected $filter_fields = ['instanceID'];

	/**
	 * Creates a new entry in the booking table for the given instance.
	 *
	 * @return int the id of the booking entry
	 */
	public function add(): int
	{
		if (!$userID = Helpers\Users::getID())
		{
			Helpers\OrganizerHelper::error(401);
		}

		if (!$instanceID = Helpers\Input::getID())
		{
			Helpers\OrganizerHelper::error(400);
		}

		if (!Helpers\Can::manage('instance', $instanceID))
		{
			Helpers\OrganizerHelper::error(403);
		}

		$block    = new Tables\Blocks();
		$instance = new Tables\Instances();
		if (!$instance->load($instanceID) or !$block->load($instance->blockID))
		{
			Helpers\OrganizerHelper::error(412);
		}

		$booking = new Tables\Bookings();
		$keys    = ['blockID' => $instance->blockID, 'unitID' => $instance->unitID];

		if (!$booking->load($keys))
		{
			$hash   = hash('adler32', (int) $instance->blockID . $instance->unitID);
			$values = ['code' => substr($hash, 0, 4) . '-' . substr($hash, 4)];

			if ($booking->save(array_merge($keys, $values)))
			{
				Helpers\OrganizerHelper::message('ORGANIZER_BOOKING_CREATED', 'success');
			}
			else
			{
				Helpers\OrganizerHelper::message('ORGANIZER_BOOKING_NOT_CREATED', 'error');
			}
		}

		return $booking->id;
	}

	/**
	 * Adds a participant to the instance(s) of the booking.
	 *
	 * @return void
	 */
	public function addParticipant()
	{
		$this->authorize();

		$listItems = Helpers\Input::getListItems();
		$input     = $listItems->get('username');

		if (empty($input) or !$input = trim($input))
		{
			Helpers\OrganizerHelper::error(400);
		}

		$bookingID = Helpers\Input::getID();

		// Manually unset the username so it isn't later added to the state
		Helpers\Input::getInput()->set('list', ['fullordering' => $listItems->get('fullordering')]);

		$existing = true;
		$query    = Database::getQuery();
		$query->select('id')->from('#__users')->where("username = " . $query->quote($input));
		Database::setQuery($query);

		if ($participantID = Database::loadInt())
		{
			if (!Helpers\Participants::exists($participantID))
			{
				$participant = new Participant();
				$participant->supplement($participantID);
				$existing = false;
			}
		}
		else
		{
			$input   = mb_convert_encoding($input, 'ISO-8859-1', 'utf-8');
			$content = http_build_query(['name' => $input]);
			$header  = "Content-type: application/x-www-form-urlencoded\r\n";
			$context = stream_context_create(['http' => ['header' => $header, 'method' => 'POST', 'content' => $content]]);

			if (!$response = file_get_contents('https://scripts.its.thm.de/emsearch/emsearch.cgi', false, $context))
			{
				Helpers\OrganizerHelper::message('ORGANIZER_503', 'error');

				return;
			}

			// Determine the response charset
			$charset = 'utf-8';
			foreach ($http_response_header as $httpHeader)
			{
				$position = strpos($httpHeader, 'charset=');
				if ($position !== false)
				{
					$charset = substr($httpHeader, $position + strlen('charset='));
				}
			}

			$count  = substr_count($response, '<li>');
			$over30 = strpos($response, 'mehr als 30') !== false;

			if ($count > 1 or $over30)
			{
				$message = sprintf(Helpers\Languages::_('ORGANIZER_TOO_MANY_RESULTS'), $input);
				Helpers\OrganizerHelper::message($message, 'notice');

				return;
			}
			elseif (!$count)
			{
				Helpers\OrganizerHelper::message('ORGANIZER_EMPTY_RESULT_SET', 'notice');

				return;
			}

			// Remove characters upto and and after li-tags inclusively
			$response = mb_convert_encoding($response, 'utf-8', $charset);
			$response = substr($response, strpos($response, '<li>') + 4);
			$response = substr($response, 0, strpos($response, '</li>'));
			$email    = $name = $username = '';

			// Attributes are unique to tags now
			if (preg_match('/<b>(.*?)<\/b>/', $response, $matches))
			{
				$name = $matches[1];
			}
			if (preg_match('/<i>(.*?)<\/i>/', $response, $matches))
			{
				$username = $matches[1];
			}
			if (preg_match('/<a[^>]*>(.*?)<\/a>/', $response, $matches))
			{
				$email = $matches[1];
			}

			// Avoid potential inconsistent external data delivery
			if (!$email or !$name or !$username)
			{
				Helpers\OrganizerHelper::message('ORGANIZER_412', 'error');

				return;
			}

			$query->clear('where');
			$query->where("username = '$username'");
			Database::setQuery($query);
			$userNameID = Database::loadInt();

			$query->clear('where');
			$query->where("email = '$email'");

			if ($userNameID)
			{
				$query->where("id != $userNameID");
			}

			Database::setQuery($query);
			$emailID = Database::loadInt();

			// These cannot be the same because of the email query's construction
			if ($userNameID and $emailID)
			{
				$userNameParticipant = new Tables\Participants();
				$emailParticipant    = new Tables\Participants();

				// One of the users does not exist as a participant (best case)
				if (!$userNameParticipant->load($userNameID) or !$emailParticipant->load($emailID))
				{
					if ($userNameParticipant->id)
					{
						$deleteID      = $emailID;
						$participantID = $userNameID;
					}
					else
					{
						$deleteID      = $userNameID;
						$participantID = $emailID;
					}
				}
				// Merge
				else
				{
					$deleteID      = $emailID;
					$participantID = $userNameID;

					foreach (array_keys($this->_db->getTableColumns('#__organizer_participants')) as $column)
					{
						if ($column === 'id')
						{
							continue;
						}

						if (!$userNameParticipant->$column and $emailParticipant->$column)
						{
							$userNameParticipant->$column = $emailParticipant->$column;
						}
					}

					$userNameParticipant->store();
					$this->reReference('course', $participantID, $emailID, 'courseID');
					$this->reReference('instance', $participantID, $emailID, 'instanceID');
				}

				$user = new User();
				$user->load($deleteID);
				$user->delete();
			}
			elseif ($userNameID or $emailID)
			{
				$participantID = $userNameID ? $userNameID : $emailID;
			}
			else
			{
				$data     = [
					'block'    => 0,
					'email'    => $email,
					'groups'   => [2],
					'name'     => $name,
					'username' => $username
				];
				$existing = false;

				$user = new User();
				$user->bind($data);
				$user->save();

				if (!$participantID = $user->id)
				{
					Helpers\OrganizerHelper::message('ORGANIZER_PARTICIPANT_NOT_IMPORTED', 'error');

					return;
				}
			}

			$participant = new Participant();
			$participant->supplement($userNameID, true);
		}

		$instanceIDs = Helpers\Bookings::getInstanceIDs($bookingID);

		// Check for existing entries in an existing participant's personal schedule
		if ($existing)
		{
			$query = Database::getQuery();
			$query->select('id')
				->from('#__organizer_instance_participants')
				->where("participantID = $participantID")
				->where('instanceID IN (' . implode(',', $instanceIDs) . ')');
			Database::setQuery($query);

			if ($ipaIDs = Database::loadIntColumn())
			{
				foreach ($ipaIDs as $ipaID)
				{
					$participation = new Tables\InstanceParticipants();
					$participation->load($ipaID);
					$participation->attended = 1;

					if (!$participation->store())
					{
						Helpers\OrganizerHelper::message('ORGANIZER_PARTICIPANT_NOT_ADDED', 'error');

						return;
					}
				}

				Helpers\OrganizerHelper::message('ORGANIZER_PARTICIPANT_ADDED', 'success');

				return;
			}
		}

		$data = ['attended' => 1, 'participantID' => $participantID];
		foreach ($instanceIDs as $instanceID)
		{
			$data['instanceID'] = $instanceID;
			$participation      = new Tables\InstanceParticipants();
			if (!$participation->save($data))
			{
				Helpers\OrganizerHelper::message('ORGANIZER_PARTICIPANT_NOT_ADDED', 'error');

				return;
			}
		}

		Helpers\OrganizerHelper::message('ORGANIZER_PARTICIPANT_ADDED', 'success');
	}

	/**
	 * Performs authorization checks for booking dm functions.
	 *
	 * @return void
	 */
	private function authorize()
	{
		if (!$bookingID = Helpers\Input::getID())
		{
			Helpers\OrganizerHelper::error(400);
		}

		if (!Helpers\Can::manage('booking', $bookingID))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * Updates the selected participation entries with the selected instance and/or room.
	 *
	 * @return bool
	 */
	public function batch(): bool
	{
		$this->authorize();

		$batch      = Helpers\Input::getBatchItems();
		$instanceID = (int) $batch->get('instanceID');
		$roomID     = (int) $batch->get('roomID');

		if (!$instanceID and !$roomID)
		{
			return true;
		}

		foreach (Helpers\Input::getSelectedIDs() as $participationID)
		{
			$participation = new Tables\InstanceParticipants();

			if (!$participation->load($participationID))
			{
				return false;
			}

			if ($instanceID)
			{
				$participation->instanceID = $instanceID;
			}

			if ($roomID)
			{
				$participation->roomID = $roomID;
			}

			if (!$participation->store())
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Deletes booking unassociated with attendance.
	 *
	 * @return void
	 */
	public function clean()
	{
		$today = '2020-12-18';//date('Y-m-d');
		$query = Database::getQuery();
		$query->select('DISTINCT bk.id')
			->from('#__organizer_bookings AS bk')
			->innerJoin('#__organizer_blocks AS bl ON bl.id = bk.blockID')
			->where("bl.date < '$today'");
		Database::setQuery($query);

		if (!$allIDs = Database::loadColumn())
		{
			Helpers\OrganizerHelper::message(Helpers\Languages::_('ORGANIZER_BOOKINGS_NOT_DELETED'), 'notice');

			return;
		}

		$query->innerJoin('#__organizer_instances AS i ON i.blockID = bk.blockID AND i.unitID = bk.unitID')
			->innerJoin('#__organizer_instance_participants AS ip ON ip.instanceID = i.id')
			->where('ip.attended = 1');
		Database::setQuery($query);

		if (!$attendedIDs = Database::loadColumn())
		{
			Helpers\OrganizerHelper::message(Helpers\Languages::_('ORGANIZER_BOOKINGS_NOT_DELETED'), 'notice');

			return;
		}

		if (!$unAttendedIDs = array_diff($allIDs, $attendedIDs))
		{
			Helpers\OrganizerHelper::message(Helpers\Languages::_('ORGANIZER_BOOKINGS_NOT_DELETED'), 'notice');

			return;
		}

		$query = Database::getQuery();
		$query->delete('#__organizer_bookings')->where('id IN (' . implode(',', $unAttendedIDs) . ')');
		Database::setQuery($query);

		if (Database::execute())
		{
			$constant = 'ORGANIZER_BOOKINGS_DELETED';
			$type     = 'success';
		}
		else
		{
			$constant = 'ORGANIZER_BOOKINGS_NOT_DELETED';
			$type     = 'error';
		}

		Helpers\OrganizerHelper::message(Helpers\Languages::_($constant), $type);
	}

	/**
	 * Closes a booking manually.
	 *
	 * @return void
	 */
	public function close()
	{
		$this->authorize();

		$block     = new Tables\Blocks();
		$booking   = new Tables\Bookings();
		$bookingID = Helpers\Input::getID();

		if (!$booking->load($bookingID) or !$block->load($booking->blockID))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_412', 'error');

			return;
		}

		$now   = date('H:i:s');
		$today = date('Y-m-d');

		if ($block->date === $today and $now > $block->startTime)
		{
			$booking->endTime = $now;

			if ($booking->store())
			{
				Helpers\OrganizerHelper::message('ORGANIZER_BOOKING_CLOSED', 'success');

				return;
			}
		}

		Helpers\OrganizerHelper::message('ORGANIZER_BOOKING_NOT_CLOSED', 'notice');
	}

	/**
	 * @inheritDoc
	 */
	protected function filterFilterForm(Form &$form)
	{
		parent::filterFilterForm($form);

		$bookingID = Helpers\Input::getID();

		if (!$this->adminContext)
		{
			$form->removeField('limit', 'list');
		}

		if (count(Helpers\Bookings::getInstanceOptions($bookingID)) === 1)
		{
			$form->removeField('instanceID', 'filter');
			unset($this->filter_fields[array_search('instanceID', $this->filter_fields)]);
		}

		if (count(Helpers\Bookings::getRoomOptions($bookingID)) <= 1)
		{
			$form->removeField('roomID', 'filter');
			unset($this->filter_fields[array_search('roomID', $this->filter_fields)]);
		}
	}

	/**
	 * Gets the booking table entry, and fills appropriate form field values.
	 *
	 * @return Tables\Bookings
	 */
	public function getBooking(): Tables\Bookings
	{
		$bookingID = Helpers\Input::getID();
		$booking   = new Tables\Bookings();
		$booking->load($bookingID);

		$block = new Tables\Blocks();
		$block->load($booking->blockID);
		$booking->set('date', $block->date);
		$booking->set('defaultEndTime', $block->endTime);
		$booking->set('defaultStartTime', $block->startTime);

		return $booking;
	}

	/**
	 * @inheritDoc
	 */
	protected function getListQuery()
	{
		$bookingID = Helpers\Input::getID();
		$query     = parent::getListQuery();
		$query->select('r.name AS room, ip.id AS ipaID, ip.seat')
			->innerJoin('#__organizer_instance_participants AS ip ON ip.participantID = pa.id')
			->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
			->innerJoin('#__organizer_bookings AS b ON b.blockID = i.blockID AND b.unitID = i.unitID')
			->leftJoin('#__organizer_rooms AS r ON r.id = ip.roomID')
			->where("b.id = $bookingID")
			->where('ip.attended = 1');

		$this->setValueFilters($query, ['instanceID', 'roomID']);

		return $query;
	}

	/**
	 * @inheritDoc
	 */
	public function getItems(): array
	{
		$bookingID = Helpers\Input::getID();
		$query     = Database::getQuery();
		$tag       = Helpers\Languages::getTag();
		$query->select("e.name_$tag AS event")
			->from('#__organizer_events AS e')
			->innerJoin('#__organizer_instances AS i ON i.eventID = e.id')
			->innerJoin('#__organizer_bookings AS b ON b.blockID = i.blockID AND b.unitID = i.unitID')
			->innerJoin('#__organizer_instance_participants AS ip ON ip.instanceID = i.id');

		$rooms      = Helpers\Bookings::getRooms($bookingID);
		$updateID   = 0;
		$updateRoom = '';

		if (count($rooms) === 1)
		{
			$updateID   = array_key_first($rooms);
			$updateRoom = reset($rooms);
		}

		foreach ($items = parent::getItems() as $key => $item)
		{
			if (empty($item->room) and $updateID)
			{
				$table = new Tables\InstanceParticipants();
				$table->load($item->ipaID);
				$table->roomID = $updateID;
				$table->store();
				$item->room = $updateRoom;
			}

			$columns        = ['address', 'city', 'forename', 'surname', 'telephone', 'zipCode'];
			$item->complete = true;

			foreach ($columns as $column)
			{
				if (empty($item->$column))
				{
					$item->complete = false;
					continue;
				}
			}

			$query->clear('where');
			$query->where("b.id = $bookingID")->where("ip.participantID = $item->id");
			Database::setQuery($query);

			if ($events = Database::loadColumn())
			{
				$item->event = count($events) > 1 ? Helpers\Languages::_('ORGANIZER_MULTIPLE_EVENTS') : $events[0];
			}
			else
			{
				$item->event = '';
			}

		}

		return $items ? $items : [];
	}

	/**
	 * @inheritDoc
	 */
	protected function loadForm($name, $source = null, $options = [], $clear = false, $xpath = false)
	{
		$form    = parent::loadForm($name, $source, $options, $clear, $xpath);
		$booking = $this->getBooking();
		$form->setValue('notes', 'supplement', $booking->notes);

		return $form;
	}

	/**
	 * Opens/reopens a booking manually.
	 *
	 * @return void
	 */
	public function open()
	{
		$this->authorize();

		$block     = new Tables\Blocks();
		$booking   = new Tables\Bookings();
		$bookingID = Helpers\Input::getID();

		if (!$booking->load($bookingID) or !$block->load($booking->blockID))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_412', 'error');

			return;
		}

		$now  = date('H:i:s');
		$then = date('H:i:s', strtotime('-60 minutes', strtotime($block->startTime)));

		// Reopen before default end
		if ($booking->endTime and $now > $booking->endTime and $now < $block->endTime)
		{
			$booking->endTime = null;

			if ($booking->store())
			{
				Helpers\OrganizerHelper::message('ORGANIZER_BOOKING_REOPENED', 'success');
			}

			return;
		}

		// Early start
		if ($now > $then and (empty($booking->startTime) or $now < $booking->startTime))
		{
			$booking->startTime = $now;

			if ($booking->store())
			{
				Helpers\OrganizerHelper::message('ORGANIZER_BOOKING_OPENED', 'success');

				return;
			}
		}

		Helpers\OrganizerHelper::message('ORGANIZER_BOOKING_NOT_OPENED', 'notice');
	}

	/**
	 * @inheritDoc
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		if (Helpers\Input::getListItems()->get('username'))
		{
			$this->addParticipant();
		}

		parent::populateState($ordering, $direction);
	}

	/**
	 * Removes the selected participants from the list of registered participants.
	 *
	 * @return void
	 */
	public function removeParticipants()
	{
		$this->authorize();

		if (!$participationIDs = Helpers\Input::getSelectedIDs())
		{
			Helpers\OrganizerHelper::message('ORGANIZER_400', 'warning');

			return;
		}

		foreach ($participationIDs as $participationID)
		{
			$table = new Tables\InstanceParticipants();

			if (!$table->load($participationID))
			{
				Helpers\OrganizerHelper::message('ORGANIZER_412', 'notice');

				return;
			}

			if (!$table->delete())
			{
				Helpers\OrganizerHelper::message('ORGANIZER_PARTICIPANTS_NOT_REMOVED', 'error');

				return;
			}
		}

		Helpers\OrganizerHelper::message('ORGANIZER_PARTICIPANTS_REMOVED', 'success');
	}

	/**
	 * Re-references entries in the course/instance participants tables for the given participant ids.
	 *
	 * @param   string  $table     the unique part of the table name (course|instance)
	 * @param   int     $toID      the id to use in the reference tables
	 * @param   int     $fromID    the id to replace/delete in the reference tables
	 * @param   string  $fkColumn  the fk column name away from the instances table (courseID|instanceID)
	 *
	 * @return void
	 */
	private function reReference(string $table, int $toID, int $fromID, string $fkColumn)
	{
		$buffer    = [];
		$fqClass   = "Organizer\\Tables\\" . ucfirst($table) . 'Participants';
		$protected = ['id', 'instanceID', $fkColumn];

		$query = Database::getQuery();
		$query->select('*')->from("#__organizer_{$table}_participants")->where("instanceID IN ($toID, $fromID)");
		Database::setQuery($query);
		$references = Database::loadAssocList();

		// Delete redundant entries buffering necessary values
		foreach ($references as $reference)
		{
			$index = $reference[$fkColumn];

			if ($entry = $buffer[$index])
			{
				foreach (array_keys($entry) as $column)
				{
					if (in_array($column, $protected))
					{
						continue;
					}

					// If one reference attended, paid, ... it is valid for all
					if ($reference[$column] > $entry[$column])
					{
						$entry[$column] = $reference[$column];
					}
				}

				$buffer[$index] = $entry;

				$table = new $fqClass();
				$table->delete($reference['id']);
			}
			else
			{
				$buffer[$index] = $reference;
			}
		}

		foreach ($buffer as $index => $entry)
		{
			$table = new $fqClass();
			$table->load($entry['id']);
			$table->save($entry);
		}
	}

	/**
	 * Saves supplemental information about the entry.
	 *
	 * @return void
	 */
	public function supplement()
	{
		$this->authorize();
		$bookingID  = Helpers\Input::getID();
		$supplement = Helpers\Input::getSupplementalItems();

		if (!$bookingID or !$notes = $supplement->get('notes'))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_400');

			return;
		}

		$booking = new Tables\Bookings();

		if (!$booking->load($bookingID))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_412', 'notice');

			return;
		}

		$booking->notes = $notes;

		if ($booking->store())
		{
			Helpers\OrganizerHelper::message('ORGANIZER_CHANGES_SAVED', 'success');
		}
		else
		{
			Helpers\OrganizerHelper::message('ORGANIZER_CHANGES_NOT_SAVED', 'success');
		}
	}
}
