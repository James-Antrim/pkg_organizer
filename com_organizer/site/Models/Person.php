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
 * Class which manages stored person data.
 */
class Person extends MergeModel
{
	use Associated;

	protected $resource = 'person';

	/**
	 * Activates persons by id if a selection was made, otherwise by use in the instance_persons table.
	 *
	 * @return bool true on success, otherwise false
	 */
	public function activate(): bool
	{
		$this->selected = Helpers\Input::getSelectedIDs();
		$this->authorize();

		// Explicitly selected resources
		if ($this->selected)
		{
			foreach ($this->selected as $selectedID)
			{
				$person = new Tables\Persons();

				if ($person->load($selectedID))
				{
					$person->active = 1;
					$person->store();
					continue;
				}

				return false;
			}

			return true;
		}

		// Implicitly used resources
		$subQuery = Database::getQuery();
		$subQuery->select('DISTINCT personID')->from('#__organizer_instance_persons');
		$query = Database::getQuery();
		$query->update('#__organizer_persons')->set('active = 1')->where("id IN ($subQuery)");
		Database::setQuery($query);

		return Database::execute();
	}

	/**
	 * @inheritDoc
	 */
	protected function authorize()
	{
		if (!Helpers\Can::edit('persons', $this->selected))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * Deactivates persons by id if a selection was made, otherwise by lack of use in the instance_persons table.
	 *
	 * @return bool true on success, otherwise false
	 */
	public function deactivate(): bool
	{
		$this->selected = Helpers\Input::getSelectedIDs();
		$this->authorize();

		// Explicitly selected resources
		if ($this->selected)
		{
			foreach ($this->selected as $selectedID)
			{
				$person = new Tables\Persons();

				if ($person->load($selectedID))
				{
					$person->active = 0;
					$person->store();
					continue;
				}

				return false;
			}

			return true;
		}

		// Implicitly unused resources
		$subQuery = Database::getQuery();
		$subQuery->select('DISTINCT personID')->from('#__organizer_instance_persons');
		$query = Database::getQuery();
		$query->update('#__organizer_persons')->set('active = 0')->where("id NOT IN ($subQuery)");
		Database::setQuery($query);

		return Database::execute();
	}

	/**
	 * Gets the resource ids associated with persons in association tables.
	 *
	 * @param   string  $table     the unique portion of the table name
	 * @param   string  $fkColumn  the name of the fk column referencing the other resource
	 *
	 * @return array the ids of the resources associated
	 */
	private function getResourceIDs(string $table, string $fkColumn): array
	{
		$personIDs = implode(',', $this->selected);
		$query     = Database::getQuery();
		$query->select("DISTINCT $fkColumn")
			->from("#__organizer_$table")
			->where("personID IN ($personIDs)")
			->order("$fkColumn");
		Database::setQuery($query);

		return Database::loadIntColumn();
	}

	/**
	 * @inheritDoc
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Persons();
	}

	/**
	 * @inheritDoc
	 */
	public function save($data = [])
	{
		$this->selected = Helpers\Input::getSelectedIDs();
		$this->authorize();

		$data  = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;
		$table = new Tables\Persons();

		if (!$table->save($data))
		{
			return false;
		}

		$data['id'] = $table->id;

		if (!empty($data['organizationIDs']) and !$this->updateAssociations($data['id'], $data['organizationIDs']))
		{
			return false;
		}

		return $table->id;
	}

	/**
	 * Updates the event coordinators table to reflect the merge of the persons.
	 *
	 * @return bool true on success, otherwise false;
	 */
	private function updateEventCoordinators(): bool
	{
		if (!$eventIDs = $this->getResourceIDs('event_coordinators', 'eventID'))
		{
			return true;
		}

		$mergeID = reset($this->selected);

		foreach ($eventIDs as $eventID)
		{
			$existing = null;

			foreach ($this->selected as $personID)
			{
				$eventCoordinator = new Tables\EventCoordinators();
				$loadConditions   = ['eventID' => $eventID, 'personID' => $personID];

				// The current personID is not associated with the current eventID
				if (!$eventCoordinator->load($loadConditions))
				{
					continue;
				}

				// An existing association with the current eventID has already been found, remove potential duplicate.
				if ($existing)
				{
					$eventCoordinator->delete();
					continue;
				}

				$eventCoordinator->personID = $mergeID;
				$existing                   = $eventCoordinator;
			}

			if ($existing and !$existing->store())
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Updates the instance persons table to reflect the merge of the persons.
	 *
	 * @return bool true on success, otherwise false;
	 */
	private function updateInstancePersons(): bool
	{
		if (!$instanceIDs = $this->getResourceIDs('instance_persons', 'instanceID'))
		{
			return true;
		}

		$mergeID = reset($this->selected);

		foreach ($instanceIDs as $instanceID)
		{
			$existing = null;

			foreach ($this->selected as $personID)
			{
				$assoc   = ['instanceID' => $instanceID, 'personID' => $personID];
				$current = new Tables\InstancePersons();

				// The current personID is not associated with the current instance
				if (!$current->load($assoc))
				{
					continue;
				}

				if ($current->delta === 'removed')
				{
					$current->delete();
					continue;
				}

				if (!$existing)
				{
					$existing = $current;
					continue;
				}

				if ($current->modified < $existing->modified)
				{
					$current->delete();
					continue;
				}

				// Just take the higher id instead of re-referencing instance groups and rooms
				$existing->delete();
				$current->personID = $mergeID;
				$existing          = $current;
			}

			if ($existing and !$existing->store())
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
		if (!$this->updateAssociationsReferences())
		{
			return false;
		}

		if (!$this->updateEventCoordinators())
		{
			return false;
		}

		if (!$this->updateInstancePersons())
		{
			return false;
		}

		return $this->updateSubjectPersons();
	}

	/**
	 * Updates the subject persons table to reflect the merge of the persons.
	 *
	 * @return bool true on success, otherwise false;
	 */
	private function updateSubjectPersons(): bool
	{
		$mergeIDs = implode(', ', $this->selected);
		$query    = Database::getQuery();
		$query->select("DISTINCT subjectID, role")
			->from("#__organizer_subject_persons")
			->where("personID IN ($mergeIDs)");
		Database::setQuery($query);

		if (!$responsibilities = Database::loadAssocList())
		{
			return true;
		}

		$mergeID = reset($this->selected);

		foreach ($responsibilities as $responsibility)
		{
			$existing = null;

			foreach ($this->selected as $personID)
			{
				$responsibility['personID'] = $personID;
				$subjectPerson              = new Tables\SubjectPersons();

				// The current personID is not associated with the current responsibility
				if (!$subjectPerson->load($responsibility))
				{
					continue;
				}

				// An existing association with the current responsibility has already been found, remove potential duplicate.
				if ($existing)
				{
					$subjectPerson->delete();
					continue;
				}

				$subjectPerson->personID = $mergeID;
				$existing                = $subjectPerson;
			}

			if ($existing and !$existing->store())
			{
				return false;
			}
		}

		return true;
	}
}
