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

use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored event data.
 */
class Event extends MergeModel implements ScheduleResource
{
	/**
	 * Provides resource specific user access checks
	 *
	 * @return boolean  true if the user may edit the given resource, otherwise false
	 */
	protected function allow()
	{
		return Helpers\Can::edit('events', $this->selected);
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Events A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Events;
	}

	/**
	 * Updates the resource dependent associations
	 *
	 * @return boolean  true on success, otherwise false
	 */
	protected function updateAssociations()
	{
		return true;
	}

	/**
	 * Processes the data for an individual schedule
	 *
	 * @param   Tables\Schedule  $schedule  the schedule being processed
	 *
	 * @return bool true if the schedule was changed, otherwise false
	 */
	public function updateSchedule($schedule)
	{
		return true;
	}
}
