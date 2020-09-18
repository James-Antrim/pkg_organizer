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
 * Class which manages event categories.
 */
class Category extends BaseModel
{
	use Associated;

	protected $resource = 'category';

	/**
	 * Activates categories by id if a selection was made, otherwise by use in the instance_groups/groups tables.
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
			foreach ($this->selected as $selectedID)
			{
				$category = new Tables\Categories();

				if ($category->load($selectedID))
				{
					$category->active = 1;
					$category->store();
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
		$subQuery->select('DISTINCT categoryID')
			->from('#__organizer_instance_groups AS ig')
			->innerJoin('#__organizer_groups AS g ON g.id = ig.groupID');

		$query = $dbo->getQuery(true);
		$query->update('#__organizer_categories AS c')
			->innerJoin('#__organizer_associations AS a ON a.categoryID = c.id')
			->set('active = 1')
			->where("c.id IN ($subQuery)")
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
		if (!Helpers\Users::getUser())
		{
			Helpers\OrganizerHelper::error(401);
		}

		if (!Helpers\Can::edit('categories', $this->selected))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * Deactivates categories by id if a selection was made, otherwise by lack of use in the instance_groups/groups
	 * tables.
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
			foreach ($this->selected as $selectedID)
			{
				$category = new Tables\Categories();

				if ($category->load($selectedID))
				{
					$category->active = 0;
					$category->store();
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
		$subQuery->select('DISTINCT categoryID')
			->from('#__organizer_instance_groups AS ig')
			->innerJoin('#__organizer_groups AS g ON g.id = ig.groupID');

		$query = $dbo->getQuery(true);
		$query->update('#__organizer_categories AS c')
			->innerJoin('#__organizer_associations AS a ON a.categoryID = c.id')
			->set('active = 0')
			->where("c.id NOT IN ($subQuery)")
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
	 * @return Tables\Categories A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Categories;
	}

	/**
	 * Attempts to save the resource.
	 *
	 * @param   array  $data  the data from the form
	 *
	 * @return mixed int id of the resource on success, otherwise boolean false
	 */
	public function save($data = [])
	{
		$this->selected = Helpers\Input::getSelectedIDs();
		$this->authorize();

		$data  = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;
		$table = new Tables\Categories();

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
}
