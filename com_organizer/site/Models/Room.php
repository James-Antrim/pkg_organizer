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
 * Class which manages stored room data.
 */
class Room extends BaseModel
{
	/**
	 * Activates rooms by id if a selection was made, otherwise by use in the instance_rooms table.
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
				$room = new Tables\Rooms();

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

		// Implicitly used resources
		$dbo = Factory::getDbo();

		$subQuery = $dbo->getQuery(true);
		$subQuery->select('DISTINCT roomID')->from('#__organizer_instance_rooms');

		$query = $dbo->getQuery(true);
		$query->update('#__organizer_rooms')->set('active = 1')->where("id IN ($subQuery)");
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

		if (!Helpers\Can::manage('facilities'))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * Deactivates rooms by id if a selection was made, otherwise by lack of use in the instance_rooms table.
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
				$room = new Tables\Rooms();

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

		// Implicitly unused resources
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
