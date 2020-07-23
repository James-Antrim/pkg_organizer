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
 * Class which manages stored room data.
 */
class Room extends BaseModel
{
	/**
	 * Activates rooms by id if a selection was made, otherwise by use in the instance_rooms table.
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

			$room = new Tables\Rooms();
			foreach ($this->selected as $selectedID)
			{
				if ($room->load($selectedID))
				{
					$room->active = 1;
					$room->store();
					continue;
				}

				return false;
			}

			return true;
		}

		if (!$allowed = Helpers\Can::manage('facilities'))
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_401'), 401);
		}

		$dbo = Factory::getDbo();

		$subQuery = $dbo->getQuery(true);
		$subQuery->select('DISTINCT roomID')->from('#__organizer_instance_rooms');

		$query = $dbo->getQuery(true);
		$query->update('#__organizer_rooms')->set('active = 1')->where("id IN ($subQuery)");
		$dbo->setQuery($query);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Provides user access checks to rooms
	 *
	 * @return boolean  true if the user may edit the given resource, otherwise false
	 */
	protected function allow()
	{
		return Helpers\Can::manage('facilities');
	}

	/**
	 * Deactivates rooms by id if a selection was made, otherwise by lack of use in the instance_rooms table.
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

			$room = new Tables\Rooms();
			foreach ($this->selected as $selectedID)
			{
				if ($room->load($selectedID))
				{
					$room->active = 0;
					$room->store();
					continue;
				}

				return false;
			}

			return true;
		}

		if (!$allowed = Helpers\Can::manage('facilities'))
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_401'), 401);
		}

		$dbo = Factory::getDbo();

		$subQuery = $dbo->getQuery(true);
		$subQuery->select('DISTINCT roomID')->from('#__organizer_instance_rooms');

		$query = $dbo->getQuery(true);
		$query->update('#__organizer_rooms')->set('active = 0')->where("id NOT IN ($subQuery)");
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
	 * @return Tables\Rooms A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Rooms;
	}
}
