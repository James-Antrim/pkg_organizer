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

use Joomla\CMS\Factory;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored group data.
 */
class Group extends BaseModel
{
	use Associated;

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
		$allowed = Helpers\Can::scheduleTheseOrganizations();
		$dbo     = Factory::getDbo();

		$subQuery = $dbo->getQuery(true);
		$subQuery->select('DISTINCT groupID')->from('#__organizer_instance_groups');

		$query = $dbo->getQuery(true);
		$query->update('#__organizer_groups AS g')
			->innerJoin('#__organizer_associations AS a ON a.groupID = g.id')
			->set('active = 1')
			->where("g.id IN ($subQuery)")
			->where('a.organizationID IN (' . implode(', ', $allowed) . ')');
		$dbo->setQuery($query);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Authorizes the user.
	 *
	 * @return void
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
		$allowed = Helpers\Can::scheduleTheseOrganizations();
		$dbo     = Factory::getDbo();

		$subQuery = $dbo->getQuery(true);
		$subQuery->select('DISTINCT groupID')->from('#__organizer_instance_groups');

		$query = $dbo->getQuery(true);
		$query->update('#__organizer_groups AS g')
			->innerJoin('#__organizer_associations AS a ON a.groupID = g.id')
			->set('active = 0')
			->where("g.id NOT IN ($subQuery)")
			->where('a.organizationID IN (' . implode(', ', $allowed) . ')');
		$dbo->setQuery($query);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Groups A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Groups;
	}

	/**
	 * Sets all expired group / term associations to published.
	 *
	 * @return bool true on success, otherwise false.
	 */
	public function publishPast()
	{
		$terms = Helpers\Terms::getResources();
		$today = date('Y-m-d');

		$query = $this->_db->getQuery(true);
		$query->update('#__organizer_group_publishing')->set('published = 1');

		foreach ($terms as $term)
		{
			if ($term['endDate'] >= $today)
			{
				continue;
			}

			$query->clear('where')->where("termID = {$term['id']}");
			$this->_db->setQuery($query);

			if (!Helpers\OrganizerHelper::executeQuery('execute'))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Attempts to save the resource.
	 *
	 * @param   array  $data  the data from the form
	 *
	 * @return bool true on success, otherwise false
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
	public function toggle()
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
}
