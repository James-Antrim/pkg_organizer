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
 * Class which manages stored grid data.
 */
class Grid extends BaseModel
{
	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Grids A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Grids;
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
		if (!$this->allow())
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
		}

		$data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;

		// Save grids in json by foreach because the index is not numeric
		$periods = [];
		$index   = 1;
		if (!empty($data['grid']))
		{
			foreach ($data['grid'] as $row)
			{
				$periods[$index] = $row;
				++$index;
			}
		}

		$grid         = ['periods' => $periods, 'startDay' => $data['startDay'], 'endDay' => $data['endDay']];
		$data['grid'] = json_encode($grid, JSON_UNESCAPED_UNICODE);

		if ($data['isDefault'] and !$this->unDefaultAll())
		{
			return false;
		}

		$table = new Tables\Grids;

		return $table->save($data) ? $table->id : false;
	}

	/**
	 * Toggles the default grid.
	 *
	 * @return bool true if the default grid was changed successfully, otherwise false
	 *
	 * @throws Exception
	 */
	public function toggle()
	{
		if (!$this->allow())
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
		}

		$selected = Helpers\Input::getID();
		$table    = new Tables\Grids;

		// Entry not found or already set to default
		if (!$table->load($selected) or $table->isDefault)
		{
			return false;
		}

		if (!$this->unDefaultAll())
		{
			return false;
		}

		$table->isDefault = 1;

		return (bool) $table->store();
	}

	/**
	 * Removes the default status from all grids.
	 *
	 * @return bool true if the default status was removed from all grids, otherwise false
	 */
	private function unDefaultAll()
	{
		$query = $this->_db->getQuery(true);
		$query->update('#__organizer_grids')->set('isDefault = 0');
		$this->_db->setQuery($query);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute');
	}
}
