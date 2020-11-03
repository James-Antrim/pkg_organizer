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
 * Class which manages stored monitor data.
 */
class Monitor extends BaseModel
{
	/**
	 * Authorizes the user.
	 *
	 * @return void
	 */
	protected function authorize()
	{
		if (!Helpers\Can::manage('facilities'))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Monitors A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Monitors;
	}

	/**
	 * Attempts to save the resource.
	 *
	 * @param   array  $data  the data from the form
	 *
	 * @return mixed int id of the resource on success, otherwise bool false
	 */
	public function save($data = [])
	{
		$this->authorize();

		$data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;

		if (empty($data['roomID']))
		{
			unset($data['roomID']);
		}

		$data['content'] = $data['content'] == '-1' ? '' : $data['content'];

		return parent::save($data);
	}

	/**
	 * Saves the default behaviour as chosen in the monitor manager
	 *
	 * @return bool  true on success, otherwise false
	 */
	public function saveDefaultBehaviour()
	{
		$this->authorize();

		$monitorID   = Helpers\Input::getID();
		$plausibleID = ($monitorID > 0);

		if ($plausibleID)
		{
			$table = new Tables\Monitors();
			$table->load($monitorID);
			$table->set('useDefaults', Helpers\Input::getInt('useDefaults'));

			return $table->store();
		}

		return false;
	}

	/**
	 * Toggles the monitor's use of default settings
	 *
	 * @return bool  true on success, otherwise false
	 */
	public function toggle()
	{
		$this->authorize();

		$monitorID = Helpers\Input::getID();
		$table     = new Tables\Monitors();
		if (empty($monitorID) or !$table->load($monitorID))
		{
			return false;
		}

		$newValue = !$table->useDefaults;
		$table->set('useDefaults', $newValue);

		return $table->store();
	}
}
