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
use Organizer\Tables;

/**
 * Class which manages stored participant data.
 */
class Participant extends MergeModel
{
//	private function anonymize()
//	{
//		/**
//		 * Anonymize user:
//		 * name: 'Anonymous User'
//		 * username: 'x-<id>'
//		 * password: ''
//		 * email: x.<id>@xx.xx
//		 * block: 1
//		 * sendEmail: 0
//		 * registerDate: '0000-00-00 00:00:00'
//		 * lastvisitDate: '0000-00-00 00:00:00'
//		 * activation: ''
//		 * params: '{}'
//		 * lastResetTime: '0000-00-00 00:00:00'
//		 * resetCount: 0
//		 * otpKey: ''
//		 * otep: ''
//		 * requireReset: 0
//		 * authProvider: ''
//		 */
//
//		/**
//		 * Anonymize participant:
//		 *
//		 */
//	}

	/**
	 * Filters names (city, forename, surname) for actual letters and accepted special characters.
	 *
	 * @param   string  $name  the raw value
	 *
	 * @return string the cleaned value
	 */
	private function cleanAlpha(string $name): string
	{
		$name = preg_replace('/[^A-ZÀ-ÖØ-Þa-zß-ÿ\p{N}_.\-\']/', ' ', $name);

		return self::cleanSpaces($name);
	}

	/**
	 * Filters names (city, forename, surname) for actual letters and accepted special characters.
	 *
	 * @param   string  $name  the raw value
	 *
	 * @return string the cleaned value
	 */
	private function cleanAlphaNum(string $name): string
	{
		$name = preg_replace('/[^A-ZÀ-ÖØ-Þa-zß-ÿ\d\p{N}_.\-\']/', ' ', $name);

		return self::cleanSpaces($name);
	}

	/**
	 * Filters out extra spaces.
	 *
	 * @param   string  $string  the raw value
	 *
	 * @return string the cleaned value
	 */
	private function cleanSpaces(string $string): string
	{
		return preg_replace('/ +/', ' ', $string);
	}

	/**
	 * @inheritDoc
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Participants();
	}

	/**
	 * Merges resource entries and cleans association tables.
	 *
	 * @return bool  true on success, otherwise false
	 * @throws Exception
	 */
	public function merge(): bool
	{
		$data = empty($this->data) ? Helpers\Input::getFormItems()->toArray() : $this->data;

		if (empty($data['email']))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_NO_EMAIL_ADDRESS_SELECTED', 'error');

			return false;
		}

		//todo get view name (booking|course|participants)
		$this->selected = Helpers\Input::getSelectedIDs();
		sort($this->selected);

		//todo differentiate rights by the called view and resource id as applicable
		if (!Helpers\Can::administrate())
		{
			Helpers\OrganizerHelper::error(403);
		}

		// Associations have to be updated before entity references are deleted by foreign keys
		if (!$this->updateReferences())
		{
			// Messages are generated at point of failure.
			return false;
		}

		$email      = $data['email'];
		$mergeID    = array_shift($this->selected);
		$data['id'] = $mergeID;

		$participant = new Tables\Participants();
		$participant->load($mergeID);

		// Case sensitive information was not being properly set without this call and store instead of save.
		$participant->bind($data);

		// Participants table has no unique columns so saving before deleting duplicates is fine.
		$participant->store();

		$user = Helpers\Users::getUser($mergeID);

		if ($user->email === $email)
		{
			$name     = $user->name;
			$password = $user->password;
			$username = $user->username;
		}
		else
		{
			$name     = '';
			$password = '';
			$username = '';
		}

		$groups        = $user->groups;
		$lastvisitDate = $user->lastvisitDate;
		$registerDate  = $user->registerDate;

		foreach ($this->selected as $deprecatedID)
		{
			$thisUser = Helpers\Users::getUser($deprecatedID);

			if ($thisUser->email === $email)
			{
				$name     = $thisUser->name;
				$password = $thisUser->password;
				$username = $thisUser->username;
			}

			$groups        = array_merge($groups, $thisUser->groups);
			$laterUse      = $thisUser->lastvisitDate > $lastvisitDate;
			$lastvisitDate = $laterUse ? $thisUser->lastvisitDate : $lastvisitDate;
			$registerDate  = $registerDate < $thisUser->registerDate ? $registerDate : $thisUser->registerDate;

			if ($thisUser->params)
			{
				$theseParams = json_decode($thisUser->params);

				foreach ($theseParams as $property => $value)
				{
					if (!$laterUse)
					{
						$user->getParam($property, $value);
					}

					$user->setParam($property, $value);
				}
			}

			if (!$thisUser->delete())
			{
				Helpers\OrganizerHelper::message('ORGANIZER_USER_DELETION_FAILED', 'error');

				return false;
			}
		}

		$user->email         = $email;
		$user->groups        = $groups;
		$user->lastvisitDate = $lastvisitDate;
		$user->name          = $name;
		$user->password      = $password;
		$user->registerDate  = $registerDate;
		$user->username      = $username;

		if (!$user->save())
		{
			return false;
		}

		return true;
	}

	/**
	 * Truncates the participation to the threshold set in the component parameters. Called automatically by the system
	 * plugin when administrator logs in.
	 *
	 * @return void
	 */
	public function truncateParticipation()
	{
		$threshold = (int) Input::getParams()->get('truncateHistory');

		if (!$threshold)
		{
			return;
		}

		$then = date('Y-m-d', strtotime("-$threshold days"));

		$query = Database::getQuery();
		$query->select('ip.*')
			->from('#__organizer_instance_participants AS ip')
			->innerJoin('#__organizer_instances AS i ON i.id = ip.instanceID')
			->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
			->where("b.date <= '$then'");
		Database::setQuery($query);

		$entries = Database::loadObjectList();

		$instances = [];

		foreach ($entries as $entry)
		{
			$instanceID = $entry->instanceID;

			if (empty($instances[$instanceID]))
			{
				$instances[$instanceID] = [
					'attended'   => (int) $entry->attended,
					'bookmarked' => 1,
					'registered' => (int) $entry->registered
				];
			}
			else
			{
				$instances[$instanceID]['attended']   += (int) $entry->attended;
				$instances[$instanceID]['bookmarked'] += 1;
				$instances[$instanceID]['registered'] += (int) $entry->registered;
			}

			$table = new Tables\InstanceParticipants();

			if ($table->load($entry->id))
			{
				$table->delete();
			}
		}

		foreach ($instances as $instanceID => $participation)
		{
			$table = new Tables\Instances();

			if (!$table->load($instanceID))
			{
				continue;
			}

			$table->attended   = $participation['attended'];
			$table->bookmarked = $participation['bookmarked'];
			$table->registered = $participation['registered'];

			$table->store();
		}
	}

	/**
	 * @inheritDoc
	 */
	public function save(array $data = [])
	{
		$data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;

		if (!isset($data['id']))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_400', 'error');

			return false;
		}

		if (!Helpers\Can::edit('participant', $data['id']))
		{
			Helpers\OrganizerHelper::error(403);
		}

		$numericFields = ['id', 'programID'];

		switch (Helpers\Input::getTask())
		{
			case 'participants.save':
				$requiredFields = ['address', 'city', 'forename', 'id', 'programID', 'surname', 'zipCode'];
				break;
			case 'checkin.contact':
				$requiredFields = ['address', 'city', 'forename', 'id', 'surname', 'telephone', 'zipCode'];
				break;
			default:
				Helpers\OrganizerHelper::error(501);

				return false;

		}

		foreach ($data as $index => $value)
		{
			if (in_array($index, $requiredFields))
			{
				$data[$index] = trim($value);

				if (empty($data[$index]))
				{
					Helpers\OrganizerHelper::message('ORGANIZER_400', 'warning');

					return false;
				}

				if (in_array($index, $numericFields) and !is_numeric($value))
				{
					Helpers\OrganizerHelper::message('ORGANIZER_400', 'warning');

					return false;
				}
			}
		}

		$data['address']   = self::cleanAlphaNum($data['address']);
		$data['city']      = self::cleanAlpha($data['city']);
		$data['forename']  = self::cleanAlpha($data['forename']);
		$data['surname']   = self::cleanAlpha($data['surname']);
		$data['telephone'] = empty($data['telephone']) ? '' : self::cleanAlphaNum($data['telephone']);
		$data['zipCode']   = self::cleanAlphaNum($data['zipCode']);

		$table = new Tables\Participants();

		if ($table->load($data['id']))
		{
			$altered = false;

			foreach ($data as $property => $value)
			{
				if (property_exists($table, $property))
				{
					$table->set($property, $value);
					$altered = true;
				}
			}

			if ($altered)
			{
				if ($table->store())
				{
					Helpers\OrganizerHelper::message('ORGANIZER_CHANGES_SAVED', 'success');
				}
				else
				{
					Helpers\OrganizerHelper::message('ORGANIZER_CHANGES_NOT_SAVED', 'error');
				}
			}

			return $data['id'];
		}

		// 'Manual' insertion because the table's primary key is also a foreign key.
		$relevantData = (object) $data;

		foreach ($relevantData as $property => $value)
		{
			if (!property_exists($table, $property))
			{
				unset($relevantData->$property);
			}
		}

		if (Database::insertObject('#__organizer_participants', $relevantData))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_PARTICIPANT_ADDED', 'success');

			return $data['id'];
		}

		Helpers\OrganizerHelper::message('ORGANIZER_PARTICIPANT_NOT_ADDED', 'success');

		return false;
	}

	/**
	 * Adds an organizer participant based on the information in the users table.
	 *
	 * @param   int   $participantID  the id of the participant/user entries
	 * @param   bool  $force          forces update of the columns derived from information in the user table
	 *
	 * @return void
	 */
	public function supplement(int $participantID, bool $force = false)
	{
		$exists = Helpers\Participants::exists($participantID);

		if ($exists and !$force)
		{
			return;
		}

		$names = Helpers\Users::resolveUserName($participantID);
		$query = Database::getQuery();

		$forename = $query->quote($names['forename']);
		$surname  = $query->quote($names['surname']);

		if (!$exists)
		{
			$query->insert('#__organizer_participants')
				->columns('id, forename, surname')
				->values("$participantID, $forename, $surname");
		}
		else
		{
			$query->update('#__organizer_persons')
				->set("forename = $forename")
				->set("surname = $surname")
				->where("id = $participantID");
		}

		Database::setQuery($query);

		Database::execute();
	}

	/**
	 * Updates the course participants table to reflect the merge of the persons.
	 *
	 * @return bool true on success, otherwise false
	 */
	public function updateCourseParticipants(): bool
	{
		if (!$courseIDs = $this->getReferencedIDs('course_participants', 'courseID'))
		{
			return true;
		}

		$mergeID = reset($this->selected);

		foreach ($courseIDs as $courseID)
		{
			$attended   = false;
			$existing   = null;
			$paid       = false;
			$registered = '';

			foreach ($this->selected as $participantID)
			{
				$assoc   = ['courseID' => $courseID, 'participantID' => $participantID];
				$current = new Tables\CourseParticipants();

				// The current participantID is not associated with the current course
				if (!$current->load($assoc))
				{
					continue;
				}

				$attended   = (int) ($attended or $current->attended);
				$paid       = (int) ($paid or $current->paid);
				$registered = ($registered and $registered < $current->participantDate) ? $registered : $current->participantDate;

				if (!$existing)
				{
					$existing = $current;
					continue;
				}

				$current->delete();
			}

			if (!$existing)
			{
				continue;
			}

			$existing->attended        = $attended;
			$existing->paid            = $paid;
			$existing->participantDate = $registered;
			$existing->participantID   = $mergeID;

			if (!$existing->store())
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Updates the instance participants table to reflect the merge of the persons.
	 *
	 * @return bool true on success, otherwise false
	 */
	public function updateInstanceParticipants(): bool
	{
		if (!$instanceIDs = $this->getReferencedIDs('instance_participants', 'instanceID'))
		{
			return true;
		}

		$mergeID = reset($this->selected);

		foreach ($instanceIDs as $instanceID)
		{
			$attended   = false;
			$existing   = null;
			$registered = false;
			$roomID     = null;
			$seat       = null;

			foreach ($this->selected as $participantID)
			{
				$assoc   = ['instanceID' => $instanceID, 'participantID' => $participantID];
				$current = new Tables\InstanceParticipants();

				// The current participantID is not associated with the current course
				if (!$current->load($assoc))
				{
					continue;
				}

				$attended   = (int) ($attended or $current->attended);
				$registered = (int) ($registered or $current->registered);
				$roomID     = $current->roomID ?? $roomID;
				$seat       = $current->seat ?? $seat;

				if (!$existing)
				{
					$existing = $current;
					continue;
				}

				$current->delete();
			}

			if (!$existing)
			{
				continue;
			}

			$existing->attended      = $attended;
			$existing->participantID = $mergeID;
			$existing->registered    = $registered;
			$existing->roomID        = $roomID;
			$existing->seat          = $seat;

			if (!$existing->store())
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected function updateReferences(): bool
	{
		if (!$this->updateCourseParticipants())
		{
			Helpers\OrganizerHelper::message('ORGANIZER_COURSE_PARTICIPATION_MERGE_FAILED', 'error');

			return false;
		}

		if (!$this->updateInstanceParticipants())
		{
			Helpers\OrganizerHelper::message('ORGANIZER_INSTANCE_PARTICIPATION_MERGE_FAILED', 'error');

			return false;
		}

		return true;
	}
}
