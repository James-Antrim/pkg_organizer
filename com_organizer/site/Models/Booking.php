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

use JDatabaseQuery;
use Joomla\CMS\Form\Form;
use Joomla\CMS\User\User;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class retrieves information for a filtered set of participants.
 */
class Booking extends Participants
{
	protected $defaultOrdering = 'fullName';

	protected $filter_fields = ['programID'];

	/**
	 * Creates a new entry in the booking table for the given instance.
	 *
	 * @return int the id of the booking entry
	 */
	public function add()
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

		$instance = new Tables\Instances();
		if (!$instance->load($instanceID))
		{
			Helpers\OrganizerHelper::error(412);
		}

		$booking = new Tables\Bookings();
		$keys    = ['blockID' => $instance->blockID, 'unitID' => $instance->unitID];

		if (!$booking->load($keys))
		{
			$hash         = hash('adler32', (int) $instance->blockID . $instance->unitID);
			$keys['code'] = substr($hash, 0, 4) . '-' . substr($hash, 4);
			$booking->save($keys);
		}

		return $booking->id;
	}

	/**
	 * Adds a participant to the instance(s) of the booking.
	 *
	 * @return bool
	 */
	public function addParticipant()
	{
		$listItems = Helpers\Input::getListItems();
		$input     = $listItems->get('username');

		if (!$bookingID = Helpers\Input::getID() or empty($input) or !$input = trim($input))
		{
			Helpers\OrganizerHelper::error(400);
		}

		if (!Helpers\Can::manage('booking', $bookingID))
		{
			Helpers\OrganizerHelper::error(403);
		}

		// Manually unset the username so it isn't later added to the state
		Helpers\Input::getInput()->set('list', ['fullordering' => $listItems->get('fullordering')]);

		$existing  = true;
		$userQuery = $this->_db->getQuery(true);
		$userQuery->select('id')->from('#__users')->where("username = " . $userQuery->quote($input));
		$this->_db->setQuery($userQuery);

		if ($participantID = Helpers\OrganizerHelper::executeQuery('loadResult', 0))
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

				return false;
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

			if (!$count = substr_count($response, '<li>'))
			{
				Helpers\OrganizerHelper::message('ORGANIZER_EMPTY_RESULT_SET', 'error');

				return false;
			}
			elseif ($count > 1)
			{
				Helpers\OrganizerHelper::message('ORGANIZER_TOO_MANY_RESULTS', 'error');

				return false;
			}

			// Convert, remove characters upto and and after li-tags inclusively
			$response = mb_convert_encoding($response, 'utf-8', $charset);
			$response = substr($response, strpos($response, '<li>') + 4);
			$response = substr($response, 0, strpos($response, '</li>'));

			$email = $name = $username = '';

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
			}

			$userQuery->clear('where')->where("username = '$username'");
			$this->_db->setQuery($userQuery);
			$userNameID = Helpers\OrganizerHelper::executeQuery('loadResult', 0);

			$userQuery->clear('where')->where("email = '$email'");
			if ($userNameID)
			{
				$userQuery->where("id != $userNameID");
			}
			$this->_db->setQuery($userQuery);
			$emailID = Helpers\OrganizerHelper::executeQuery('loadResult', 0);

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
						$deleteID = $emailID;
					}
					else
					{
						$deleteID   = $userNameID;
						$userNameID = $emailID;
					}
				}
				// Merge
				else
				{
					$deleteID = $emailID;

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
					$this->reReference('course', $userNameID, $emailID, 'courseID');
					$this->reReference('instance', $userNameID, $emailID, 'instanceID');
				}

				$user = new User();
				$user->load($deleteID);
				$user->delete();

				// Re-reference otherID references to participantID and delete the entry
			}
			elseif ($userNameID or $emailID)
			{
				$userNameID = $emailID ? $emailID : $userNameID;
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

				if (!$userNameID = $user->id)
				{
					Helpers\OrganizerHelper::message('ORGANIZER_SAVE_FAIL', 'error');

					return false;
				}
			}

			$participantID = $userNameID;

			$query = $this->_db->getQuery(true);
			$query->update('#__users')
				->set("email = '$email'")
				->set("name = '$name'")
				->set("password = ''")
				->set("username = '$username")
				->where("id = $participantID");

			$participant = new Participant();
			$participant->supplement($userNameID, true);

		}

		$instanceIDs = Helpers\Bookings::getInstanceIDs($bookingID);

		// Check for existing entries in an existing participant's personal schedule
		if ($existing)
		{
			$query = $this->_db->getQuery(true);
			$query->select('id')
				->from('#__organizer_instance_participants')
				->where("participantID = $participantID")
				->where('instanceID IN (' . implode(',', $instanceIDs) . ')');
			$this->_db->setQuery($query);

			if ($ipIDs = Helpers\OrganizerHelper::executeQuery('loadColumn', []))
			{
				foreach ($ipIDs as $ipID)
				{
					$participation = new Tables\InstanceParticipants();
					$participation->load($ipID);
					$participation->attended = 1;
					if (!$participation->store())
					{
						return false;
					}
				}

				return true;
			}
		}

		$data = ['attended' => 1, 'participantID' => $participantID];
		foreach ($instanceIDs as $instanceID)
		{
			$data['instanceID'] = $instanceID;
			$participation      = new Tables\InstanceParticipants();
			if (!$participation->save($data))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Gets the booking table entry, and fills appropriate form field values.
	 *
	 * @return Tables\Bookings
	 */
	public function getBooking()
	{
		$bookingID = Helpers\Input::getID();
		$booking   = new Tables\Bookings();
		$booking->load($bookingID);

		return $booking;
	}

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$query = parent::getListQuery();

		$bookingID = Helpers\Input::getID();
		$query->innerJoin('#__organizer_instance_participants AS ip ON ip.participantID = pa.id')
			->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
			->innerJoin('#__organizer_bookings AS b ON b.blockID = i.blockID AND b.unitID = i.unitID')
			->where("b.id = $bookingID")
			->where('ip.attended = 1');

		return $query;
	}

	/**
	 * Wrapper method for Joomla\CMS\MVC\Model\ListModel which has a mixed return type.
	 *
	 * @return  array  An array of data items on success.
	 */
	public function getItems()
	{
		$bookingID = Helpers\Input::getID();
		$tag       = Helpers\Languages::getTag();

		$query = $this->_db->getQuery(true);
		$query->select("e.name_$tag AS event")
			->from('#__organizer_events AS e')
			->innerJoin('#__organizer_instances AS i ON i.eventID = e.id')
			->innerJoin('#__organizer_bookings AS b ON b.blockID = i.blockID AND b.unitID = i.unitID')
			->innerJoin('#__organizer_instance_participants AS ip ON ip.instanceID = i.id');

		foreach ($items = parent::getItems() as $key => $item)
		{
			$item->complete = true;

			$columns = ['address', 'city', 'forename', 'surname', 'telephone', 'zipCode'];
			foreach ($columns as $column)
			{
				if (empty($item->$column))
				{
					$item->complete = false;
					continue;
				}
			}

			$query->clear('where')->where("b.id = $bookingID")->where("ip.participantID = $item->id");
			$this->_db->setQuery($query);

			if ($events = Helpers\OrganizerHelper::executeQuery('loadColumn', []))
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
	 * Method to get a form object.
	 *
	 * @param   string       $name     The name of the form.
	 * @param   string       $source   The form source. Can be XML string if file flag is set to false.
	 * @param   array        $options  Optional array of options for the form creation.
	 * @param   bool         $clear    Optional argument to force load a new form.
	 * @param   string|bool  $xpath    An optional xpath to search for the fields.
	 *
	 * @return  Form|bool  Form object on success, False on error.
	 * @noinspection PhpDocSignatureInspection
	 * @noinspection PhpMissingParamTypeInspection
	 */
	protected function loadForm($name, $source = null, $options = array(), $clear = false, $xpath = false)
	{
		$form = parent::loadForm($name, $source, $options, $clear, $xpath);

		$booking = $this->getBooking();
		$form->setValue('notes', 'supplement', $booking->notes);

		return $form;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return void populates state properties
	 * @noinspection PhpDocSignatureInspection
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

		$query = $this->_db->getQuery(true);
		$query->select('*')->from("#__organizer_{$table}_participants")->where("instanceID IN ($toID, $fromID)");
		$this->_db->setQuery($query);
		$references = Helpers\OrganizerHelper::executeQuery('loadAssocList', []);

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
	 * @return bool
	 */
	public function supplement()
	{
		$bookingID  = Helpers\Input::getID();
		$supplement = Helpers\Input::getSupplementalItems();

		if (!$bookingID or !$notes = $supplement->get('notes'))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_400');

			return false;
		}

		$booking = new Tables\Bookings();

		if (!$booking->load($bookingID))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_412');

			return false;
		}

		$booking->notes = $notes;

		return $booking->store();
	}
}
