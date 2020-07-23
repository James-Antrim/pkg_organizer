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
use Joomla\CMS\Factory;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored person data.
 */
class Person extends BaseModel
{
	use Associated;

	protected $resource = 'person';

	/**
	 * Activates persons by id if a selection was made, otherwise by use in the instance_persons table.
	 *
	 * @return bool true on success, otherwise false
	 * @throws Exception unauthorized access
	 */
	public function activate()
	{
		if ($this->selected = Helpers\Input::getSelectedIDs())
		{
			if (!$this->allow())
			{
				throw new Exception(Helpers\Languages::_('ORGANIZER_401'), 401);
			}

			$person = new Tables\Persons();
			foreach ($this->selected as $selectedID)
			{
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

		if (!$allowed = Helpers\Can::edit('persons'))
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_401'), 401);
		}

		$dbo = Factory::getDbo();

		$subQuery = $dbo->getQuery(true);
		$subQuery->select('DISTINCT personID')->from('#__organizer_instance_persons');

		$query = $dbo->getQuery(true);
		$query->update('#__organizer_persons')->set('active = 1')->where("id IN ($subQuery)");
		$dbo->setQuery($query);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Provides user access checks to persons
	 *
	 * @return boolean  true if the user may edit the given resource, otherwise false
	 */
	protected function allow()
	{
		return Helpers\Can::edit('persons');
	}

	/**
	 * Deactivates persons by id if a selection was made, otherwise by lack of use in the instance_persons table.
	 *
	 * @return bool true on success, otherwise false
	 * @throws Exception unauthorized access
	 */
	public function deactivate()
	{
		if ($this->selected = Helpers\Input::getSelectedIDs())
		{
			if (!$this->allow())
			{
				throw new Exception(Helpers\Languages::_('ORGANIZER_401'), 401);
			}

			$person = new Tables\Persons();
			foreach ($this->selected as $selectedID)
			{
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

		if (!$allowed = Helpers\Can::edit('persons'))
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_401'), 401);
		}

		$dbo = Factory::getDbo();

		$subQuery = $dbo->getQuery(true);
		$subQuery->select('DISTINCT personID')->from('#__organizer_instance_persons');

		$query = $dbo->getQuery(true);
		$query->update('#__organizer_persons')->set('active = 0')->where("id NOT IN ($subQuery)");
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
	 * @return Tables\Persons A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Persons;
	}

	/**
	 * Attempts to save the resource.
	 *
	 * @param   array  $data  the data from the form
	 *
	 * @return mixed int id of the resource on success, otherwise boolean false
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

		$table = new Tables\Persons;

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
