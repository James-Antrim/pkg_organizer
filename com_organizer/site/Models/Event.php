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
 * Class which manages stored event data.
 */
class Event extends BaseModel
{
	/**
	 * @inheritDoc
	 */
	protected function authorize()
	{
		if ($this->selected and !Helpers\Can::edit('events', $this->selected))
		{
			Helpers\OrganizerHelper::error(403);
		}
		elseif ($eventID = Helpers\Input::getID() and !Helpers\Can::edit('events', $eventID))
		{
			Helpers\OrganizerHelper::error(403);
		}
		elseif (!Helpers\Can::edit('events'))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Events();
	}

	/**
	 * Attempts to save the resource.
	 *
	 * @param   array  $data  the data from the form
	 *
	 * @return int|bool int id of the resource on success, otherwise bool false
	 */
	public function save($data = [])
	{
		if (!$eventID = parent::save($data))
		{
			return false;
		}

		$data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;
		if ($coordinatorIDs = $data['coordinatorIDs'])
		{
			foreach ($coordinatorIDs as $coordinatorID)
			{
				$coordinator = new Tables\EventCoordinators();
				$assocData   = ['eventID' => $eventID, 'personID' => $coordinatorID];
				if (!$coordinator->load($assocData))
				{
					$coordinator->save($assocData);
				}
			}
		}

		$query = Database::getQuery();
		$query->delete('#__organizer_event_coordinators')->where("eventID = $eventID");

		if ($coordinatorIDs)
		{
			$coordinatorIDs = implode(', ', $coordinatorIDs);
			$query->where("personID NOT IN ($coordinatorIDs)");
		}

		Database::setQuery($query);
		Database::execute();

		return $eventID;
	}
}
