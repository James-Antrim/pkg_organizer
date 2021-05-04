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

use Joomla\Utilities\ArrayHelper;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored instance data.
 */
class Instance extends BaseModel
{
	private const LEADER = 5;

	private $personID;

	/**
	 * @var string
	 */
	private $modified;

	/**
	 * Checks access to edit the resource.
	 *
	 * @return void
	 */
	protected function authorize()
	{
		if (!Helpers\Users::getID())
		{
			Helpers\OrganizerHelper::error(401);
		}

		if (!$this->personID = Helpers\Persons::getIDByUserID())
		{
			Helpers\OrganizerHelper::error(403);
		}

		if ($instanceID = Helpers\Input::getID() and !Helpers\Can::manage('instance', $instanceID))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Instances();
	}

	/**
	 * @inheritDoc
	 */
	public function save($data = [])
	{
		Helpers\OrganizerHelper::error(503);

		$this->authorize();
		$data           = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;
		$this->modified = date('Y-m-d H:i:s');

		if ($data['layout'] === 'appointment')
		{
			return $this->saveAppointment($data);
		}

		// Not implemented, yet
		Helpers\OrganizerHelper::error(503);

		return false;
	}

	/**
	 * Creates/updates an appointment.
	 *
	 * @param   array  $data  the data from the form
	 *
	 * @return false|int
	 */
	private function saveAppointment($data = [])
	{
		foreach (['date', 'endTime', 'roomIDs', 'startTime', 'title'] as $required)
		{
			if (empty($data[$required]))
			{
				// Hard error
				Helpers\OrganizerHelper::error(400);
			}
		}

		$date      = $data['date'];
		$validDate = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) and $date >= date('Y-m-d'));

		$now       = date('H:i:s');
		$endTime   = $this->standardizeTime($date, $data['endTime']);
		$startTime = $this->standardizeTime($date, $data['startTime']);

		if (!$validDate or !$startTime or !$endTime or $endTime < $startTime or $endTime < $now)
		{
			// Improper use
			Helpers\OrganizerHelper::message('ORGANIZER_INSTANCE_DATE_OR_TIMES_INVALID');

			return false;
		}

		// Block modelling is hidden from the user => resolve with date and times
		$bKeys = ['date' => $date, 'endTime' => $endTime, 'startTime' => $startTime];
		$block = new Tables\Blocks();
		if (!$block->load($bKeys))
		{
			$bKeys['dow'] = date('w', strtotime($date));
			$block->save($bKeys);
		}

		$blockID = $block->id;
		unset($block);

		$instance = new Tables\Instances();
		if ($instanceID = empty($data['id']) ? null : (int) $data['id'])
		{
			if (!$instance->load($instanceID))
			{
				Helpers\OrganizerHelper::error(412);
			}

			$unitID = $instance->unitID;
		}
		else
		{
			$code   = Helpers\Users::getID() . '-1';
			$termID = Helpers\Terms::getCurrentID($date);
			$unit  = new Tables\Units();
			$unit->load(['code' => $code, 'termID' => $termID]);

			$uData = [
				'code' => $code,
				'endDate' => (!empty($results['endDate']) and $results['endDate'] > $date) ? $results['endDate'] : $date,
				'modified' => $this->modified,
				'organizationID' => null,
				'startDate' => (!empty($results['startDate']) and $results['startDate'] < $date) ? $results['startDate'] : $date,
				'termID' => $termID
			];

			$unit->save($uData);
			$unitID = $unit->id;
			unset($unit);
			$instance->load(['blockID' => $blockID, 'unitID' => $unitID]);
		}

		$iData    = [
			'blockID' => $blockID,
			'eventID' => null,
			'modified' => $this->modified,
			'title'    => Helpers\Input::filter($data['title']),
			'unitID' => $unitID
		];
		$instance->save($iData);
		$instanceID = $instance->id;
		unset($instance);

		$instancePerson = new Tables\InstancePersons();
		$ipeKeys        = ['instanceID' => $instanceID, 'personID' => $this->personID];
		$instancePerson->load($ipeKeys);
		$ipeKeys['roleID']   = self::LEADER;
		$ipeKeys['modified'] = $this->modified;
		$instancePerson->save($ipeKeys);
		$assocID = $instancePerson->id;
		unset($instancePerson);

		$existingRooms  = Helpers\Instances::getRoomIDs($instanceID);
		$requestedRooms = ArrayHelper::toInteger($data['roomIDs']);

		foreach (array_diff($existingRooms, $requestedRooms) as $deprecatedID)
		{
			$ir = new Tables\InstanceRooms();
			$ir->load(['assocID' => $assocID, 'roomID' => $deprecatedID]);
			$ir->delete();
		}

		foreach (array_diff($requestedRooms, $existingRooms) as $newID)
		{
			$keys = ['assocID' => $assocID, 'modified' => $this->modified, 'roomID' => $newID];
			$ir   = new Tables\InstanceRooms();
			$ir->load(['assocID' => $assocID, 'roomID' => $newID]);
			$ir->save($keys);
		}

		return $instanceID;
	}

	/**
	 * Validates a time attribute syntactically.
	 *
	 * @param   string  $date  the date of the instance
	 * @param   string  $time  the time being standardized
	 *
	 * @return null|string the H:i standardized value, or false if the value syntax was incorrect.
	 */
	private function standardizeTime(string $date, string $time): ?string
	{
		if (!preg_match('/^(([01]?[0-9]|2[0-3]):?[0-5][0-9])$/', $time))
		{
			return null;
		}

		return date('H:i:s', strtotime("$date $time"));
	}
}
