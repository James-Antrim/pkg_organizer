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
 * Class which manages stored participant data.
 */
class Participant extends BaseModel
{
	/**
	 * Filters names (city, forename, surname) for actual letters and accepted special characters.
	 *
	 * @param   string  $name  the raw value
	 *
	 * @return string the cleaned value
	 */
	private function cleanAlpha($name)
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
	private function cleanAlphaNum($name)
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
	private function cleanSpaces($string)
	{
		return preg_replace('/ +/', ' ', $string);
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Participants A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Participants;
	}

	/**
	 * Normalized strings used for participant name pieces.
	 *
	 * @param   string  $item  the attribute item being normalized.
	 *
	 * @return void modifies the string
	 */
	/*private function normalize(&$item)
	{
		if (strpos($item, '-') !== false)
		{
			$compoundParts = explode('-', $item);
			array_walk($compoundParts, 'normalize');
			$item = implode('-', $compoundParts);

			return;
		}

		$item = ucfirst(strtolower($item));
	}*/

	/**
	 * Attempts to save the resource.
	 *
	 * @param   array  $data  the data from the form
	 *
	 * @return mixed int id of the resource on success, otherwise bool false
	 */
	public function save($data = [])
	{
		$data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;

		if (!isset($data['id']))
		{
			return false;
		}

		if (!Helpers\Can::edit('participant', $data['id']))
		{
			Helpers\OrganizerHelper::error(403);
		}

		$numericFields  = ['id', 'programID'];
		$requiredFields = ['address', 'city', 'forename', 'id', 'programID', 'surname', 'zipCode'];

		foreach ($data as $index => $value)
		{
			if (in_array($index, $requiredFields))
			{
				$data[$index] = trim($value);
				if (empty($data[$index]))
				{
					return false;
				}
				if (in_array($index, $numericFields) and !is_numeric($value))
				{
					return false;
				}
			}
		}

		$data['address']  = self::cleanAlphaNum($data['address']);
		$data['city']     = self::cleanAlpha($data['city']);
		$data['forename'] = self::cleanAlpha($data['forename']);
		$data['surname']  = self::cleanAlpha($data['surname']);
		$data['zipCode']  = self::cleanAlphaNum($data['zipCode']);

		$success = true;
		$table   = new Tables\Participants();
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
				$success = $table->store();
			}
		}
		// Manual insertion because the table's primary key is also a foreign key.
		else
		{
			$relevantData = (object) $data;

			foreach ($relevantData as $property => $value)
			{
				if (!property_exists($table, $property))
				{
					unset($relevantData->$property);
				}
			}

			$success = Helpers\OrganizerHelper::insertObject('#__organizer_participants', $relevantData, 'id');

		}

		return $success ? $data['id'] : false;
	}

	/**
	 * Adds an organizer participant based on the information in the users table.
	 *
	 * @param   int  $participantID
	 *
	 * @return void
	 */
	public function supplement(int $participantID)
	{
		if (Helpers\Participants::exists($participantID))
		{
			return;
		}

		$names = Helpers\Users::resolveUserName($participantID);
		$query = $this->_db->getQuery(true);

		$forename = $query->quote($names['forename']);
		$surname  = $query->quote($names['surname']);

		$query->insert('#__organizer_participants')
			->columns('id, forename, surname')
			->values("$participantID, $forename, $surname");
		$this->_db->setQuery($query);

		Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Updates the users table to reflect the merge of the participants.
	 *
	 * @return bool true on success, otherwise false;
	 */
	/*private function updateUsers()
	{
		$mergeID = reset($this->selected);
		$user    = Helpers\Users::getUser($mergeID);

		if (empty($user->id))
		{
			return false;
		}

		$email    = '';
		$name     = '';
		$pattern  = '/thm.de$/';
		$username = '';

		foreach ($this->selected as $participantID)
		{
			$thisUser = Helpers\Users::getUser($participantID);

			if (preg_match($pattern, $thisUser->email))
			{
				$email    = $thisUser->email;
				$name     = $thisUser->name;
				$username = $thisUser->username;
			}

			if ($thisUser->id !== $user->id)
			{
				$thisUser->delete();
			}
		}

		$user->email    = $email;
		$user->name     = $name;
		$user->username = $username;

		return $user->save();
	}*/
}
