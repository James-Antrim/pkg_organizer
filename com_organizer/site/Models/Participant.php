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

use Organizer\Adapters\Database;
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
	private function cleanAlpha(string $name)
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
	private function cleanAlphaNum(string $name)
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
	private function cleanSpaces(string $string)
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
	 * @inheritDoc
	 */
	public function save($data = [])
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

					return $data['id'];
				}
				else
				{
					Helpers\OrganizerHelper::message('ORGANIZER_CHANGES_NOT_SAVED', 'error');

					return $data['id'];
				}
			}
			else
			{
				// Nothing changed
				return $data['id'];
			}
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
}
