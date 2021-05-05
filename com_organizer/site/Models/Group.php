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
 * Class which manages stored group data.
 */
class Group extends MergeModel
{
	use Associated;

	/**
	 * @var string
	 * @see Associated
	 */
	protected $resource = 'group';

	/**
	 * Activates groups by id if a selection was made, otherwise by use in the instance_groups table.
	 *
	 * @return bool true on success, otherwise false
	 */
	public function activate()
	{
		$this->selected = Helpers\Input::getSelectedIDs();
		$this->authorize();

		// Explicitly selected resources
		if ($this->selected)
		{
			$group = new Tables\Groups();
			foreach ($this->selected as $selectedID)
			{
				if ($group->load($selectedID))
				{
					$group->active = 1;
					$group->store();
					continue;
				}

				return false;
			}

			return true;
		}

		// Implicitly used resources
		$allowed  = Helpers\Can::scheduleTheseOrganizations();
		$subQuery = Database::getQuery();
		$subQuery->select('DISTINCT groupID')->from('#__organizer_instance_groups');
		$query = Database::getQuery();
		$query->update('#__organizer_groups AS g')
			->innerJoin('#__organizer_associations AS a ON a.groupID = g.id')
			->set('active = 1')
			->where("g.id IN ($subQuery)")
			->where('a.organizationID IN (' . implode(', ', $allowed) . ')');
		Database::setQuery($query);

		return Database::execute();
	}

	/**
	 * @inheritDoc
	 */
	protected function authorize()
	{
		if (!Helpers\Can::edit('groups', $this->selected))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * Performs batch processing of groups, specifically their publication per period and their associated grids.
	 *
	 * @return bool true on success, otherwise false
	 */
	public function batch()
	{
		if (!$this->selected = Helpers\Input::getSelectedIDs())
		{
			return false;
		}

		$this->authorize();

		if (!$this->savePublishing())
		{
			return false;
		}

		if ($gridID = Helpers\Input::getBatchItems()['gridID'])
		{
			foreach ($this->selected as $groupID)
			{
				$table = new Tables\Groups();

				if (!$table->load($groupID))
				{
					return false;
				}

				$table->gridID = $gridID;

				if (!$table->store())
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Deactivates groups by id if a selection was made, otherwise by lack of use in the instance_groups table.
	 *
	 * @return bool true on success, otherwise false
	 */
	public function deactivate()
	{
		$this->selected = Helpers\Input::getSelectedIDs();
		$this->authorize();

		// Explicitly selected resources
		if ($this->selected)
		{
			$group = new Tables\Groups();
			foreach ($this->selected as $selectedID)
			{
				if ($group->load($selectedID))
				{
					$group->active = 0;
					$group->store();
					continue;
				}

				return false;
			}

			return true;
		}

		// Implicitly unused resources
		$allowed  = Helpers\Can::scheduleTheseOrganizations();
		$subQuery = Database::getQuery();
		$subQuery->select('DISTINCT groupID')->from('#__organizer_instance_groups');
		$query = Database::getQuery();
		$query->update('#__organizer_groups AS g')
			->innerJoin('#__organizer_associations AS a ON a.groupID = g.id')
			->set('active = 0')
			->where("g.id NOT IN ($subQuery)")
			->where('a.organizationID IN (' . implode(', ', $allowed) . ')');
		Database::setQuery($query);

		return Database::execute();
	}

	/**
	 * @inheritDoc
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Groups();
	}

	/**
	 * Sets all expired group / term associations to published.
	 *
	 * @return bool true on success, otherwise false.
	 */
	public function publishPast()
	{
		$query = Database::getQuery();
		$terms = Helpers\Terms::getResources();
		$today = date('Y-m-d');
		$query->update('#__organizer_group_publishing')->set('published = 1');

		foreach ($terms as $term)
		{
			if ($term['endDate'] >= $today)
			{
				continue;
			}

			$query->clear('where');
			$query->where("termID = {$term['id']}");
			Database::setQuery($query);

			if (!Database::execute())
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function save($data = [])
	{
		$this->selected = Helpers\Input::getSelectedIDs();
		$this->authorize();

		$data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;

		$table = new Tables\Groups();

		if (!$table->save($data))
		{
			return false;
		}

		if (empty($this->savePublishing()))
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
	 * Saves the publishing data for a group.
	 *
	 * @return bool true on success, otherwise false
	 */
	private function savePublishing()
	{
		$default = false;

		if (!$terms = Helpers\Input::getBatchItems()->get('publishing'))
		{
			if (!$terms = Helpers\Input::getFormItems()->get('publishing'))
			{
				$default = true;
				$terms   = array_flip(Helpers\Terms::getIDs());
			}
		}

		foreach ($this->selected as $groupID)
		{
			foreach ($terms as $termID => $publish)
			{
				$table = new Tables\GroupPublishing();
				$data  = ['groupID' => $groupID, 'termID' => $termID];

				// Skip existing entry if no publishing state was specified
				if ($exists = $table->load($data) and $default)
				{
					continue;
				}

				$data['published'] = $exists ? $publish : 1;

				if (!$table->save($data))
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Alters the state of a binary property.
	 *
	 * @return bool true on success, otherwise false
	 */
	public function toggle(): bool
	{
		if (!$groupID = Helpers\Input::getID())
		{
			return false;
		}

		$this->selected = [$groupID];
		$this->authorize();

		$attribute = Helpers\Input::getCMD('attribute');

		if (is_numeric($attribute))
		{
			$load  = ['groupID' => $groupID, 'termID' => (int) $attribute];
			$table = new Tables\GroupPublishing();

			if (!$table->load($load))
			{
				return false;
			}

			$table->published = !$table->published;

			return $table->store();
		}
		elseif ($attribute === 'active')
		{
			$table = new Tables\Groups();

			if (!$table->load($groupID))
			{
				return false;
			}

			$table->active = !$table->active;

			return $table->store();
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	protected function updateReferences()
	{
		if (!$this->updateAssociationsReferences())
		{
			return false;
		}

		if (!$this->updateReferencingTable('pools'))
		{
			return false;
		}

		return $this->updateIPReferences();
	}
}
