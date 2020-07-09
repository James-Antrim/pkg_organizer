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
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored group data.
 */
class Group extends BaseModel
{
	use Associated;

	/**
	 * The ids selected by the user
	 *
	 * @var array
	 */
	protected $selected = [];

	/**
	 * Provides resource specific user access checks
	 *
	 * @return boolean  true if the user may edit the given resource, otherwise false
	 */
	protected function allow()
	{
		return Helpers\Can::edit('groups', $this->selected);
	}

	/**
	 * Performs batch processing of groups, specifically their publication per period and their associated grids.
	 *
	 * @return bool true on success, otherwise false
	 * @throws Exception => unauthorized access
	 */
	public function batch()
	{
		$this->selected = Helpers\Input::getSelectedIDs();

		if (empty($this->selected))
		{
			return false;
		}

		if (!Helpers\Can::edit('groups', $this->selected))
		{
			throw new Exception(Languages::_('ORGANIZER_403'), 403);
		}

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

			$query->clear('where');
			$query->where("termID = {$term['id']}");

			$this->_db->setQuery($query);
			$success = Helpers\OrganizerHelper::executeQuery('execute');
			if (!$success)
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
	 * @throws Exception => unauthorized access
	 */
	public function save($data = [])
	{
		$this->selected = Helpers\Input::getSelectedIDs();
		$data           = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;

		if (!$this->allow())
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_401'), 401);
		}

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
				$table = new Tables\GroupPublishing;
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
}
