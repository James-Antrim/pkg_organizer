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
 * Class which manages stored monitor data.
 */
class Monitor extends BaseModel
{
	/**
	 * Authenticates the user
	 */
	protected function allow()
	{
		return Helpers\Can::manage('facilities');
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
	 * save
	 *
	 * attempts to save the monitor form data
	 *
	 * @return bool true on success, otherwise false
	 * @throws Exception => unauthorized access
	 */
	public function save()
	{
		$data = Helpers\Input::getFormItems()->toArray();

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
	 * @return boolean  true on success, otherwise false
	 * @throws Exception => unauthorized access
	 */
	public function saveDefaultBehaviour()
	{
		if (!Helpers\Can::administrate())
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
		}

		$monitorID   = Helpers\Input::getID();
		$plausibleID = ($monitorID > 0);

		if ($plausibleID)
		{
			$table = new Tables\Monitors;
			$table->load($monitorID);
			$table->set('useDefaults', Helpers\Input::getInt('useDefaults'));

			return $table->store();
		}

		return false;
	}

	/**
	 * Toggles the monitor's use of default settings
	 *
	 * @return boolean  true on success, otherwise false
	 * @throws Exception => unauthorized access
	 */
	public function toggle()
	{
		if (!Helpers\Can::manage('facilities'))
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
		}

		$monitorID = Helpers\Input::getID();
		$table     = new Tables\Monitors;
		if (empty($monitorID) or !$table->load($monitorID))
		{
			return false;
		}

		$newValue = !$table->useDefaults;
		$table->set('useDefaults', $newValue);

		return $table->store();
	}
}
