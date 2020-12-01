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
 * Class which manages stored instance data.
 */
class Instance extends BaseModel
{
	/**
	 * Updates an association table's delta value.
	 *
	 * @param   Tables\BaseTable  $assoc  the association table to update
	 * @param   array             $data   the data used to identify/create
	 *
	 * @return bool true on success, otherwise false
	 */
	private function associate(Tables\BaseTable $assoc, array $data)
	{
		if ($assoc->load($data))
		{
			/** @noinspection PhpUndefinedFieldInspection */
			$assoc->delta = $assoc->delta === 'removed' ? 'new' : '';

			return $assoc->store();
		}
		else
		{
			$data['delta'] = 'new';

			return $assoc->save($data);
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
		$data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;

		$table = new Tables\Instances();
		if (!$table->save($data))
		{
			return false;
		}

		$data['id'] = $table->id;

		return $this->saveResourceData($data) ? $table->id : false;
	}

	/**
	 * Method to check the new instance data and to save it
	 *
	 * @param   array  $data  the new instance data
	 *
	 * checkAssocID to check the existing assocID or create a new one
	 *
	 * @return bool
	 */
	private function saveResourceData(array $data)
	{
		$instanceID = $data['id'];
		$ipIDs      = [];

		foreach ($data['resources'] as $person)
		{
			$ipData  = ['instanceID' => $instanceID, 'personID' => $person['personID']];
			$ipTable = new Tables\InstancePersons();
			$roleID  = !empty($person['roleID']) ? $person['roleID'] : 1;

			if ($ipTable->load($ipData))
			{
				if ($ipTable->delta === 'removed')
				{
					$ipTable->delta = 'new';
				}
				else
				{
					if ($ipTable->roleID != $roleID)
					{
						$ipTable->delta  = 'changed';
						$ipTable->roleID = $roleID;
					}
					else
					{
						$ipTable->delta = '';
					}
				}

				if (!$ipTable->store())
				{
					return false;
				}
			}
			else
			{
				$ipData['delta']  = 'new';
				$ipData['roleID'] = $roleID;
				if (!$ipTable->save($ipData))
				{
					return false;
				}
			}

			$ipID    = $ipTable->id;
			$ipIDs[] = $ipID;
			$igIDs   = [];

			foreach ($person['groups'] as $group)
			{
				$igData  = ['assocID' => $ipID, 'groupID' => $group['groupID']];
				$igTable = new Tables\InstanceGroups();
				if (!$this->associate($igTable, $igData))
				{
					return false;
				}

				$igIDs[] = $igTable->id;
			}

			$this->setRemoved('instance_groups', 'assocID', $ipID, $igIDs);

			$irIDs = [];

			foreach ($person['rooms'] as $room)
			{
				$irData  = ['assocID' => $ipID, 'roomID' => $room['roomID']];
				$irTable = new Tables\InstanceRooms();
				if (!$this->associate($irTable, $irData))
				{
					return false;
				}

				$irIDs[] = $irTable->id;
			}

			$this->setRemoved('instance_rooms', 'assocID', $ipID, $irIDs);
		}

		$this->setRemoved('instance_persons', 'instanceID', $instanceID, $ipIDs);

		return true;
	}

	/**
	 * Sets resource associations which are no longer current to 'removed';
	 *
	 * @param   string  $suffix       the unique table name ending
	 * @param   string  $assocColumn  the name of the column referencing an association
	 * @param   int     $assocValue   the value of the referenced association's id
	 * @param   array   $idValues     the values of the current resource association ids
	 *
	 * @return bool
	 */
	private function setRemoved(string $suffix, string $assocColumn, int $assocValue, array $idValues)
	{
		$query = Database::getQuery();
		$query->update("#__organizer_$suffix")
			->set("delta = 'removed'")
			->where("$assocColumn = $assocValue")
			->where('id NOT IN (' . implode(',', $idValues) . ')');

		Database::setQuery($query);

		return Database::execute();
	}
}
