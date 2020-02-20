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
	 * @param   array  $data  form data which has been preprocessed by inheriting classes.
	 *
	 * @return mixed int id of the resource on success, otherwise boolean false
	 * @throws Exception => unauthorized access
	 */
	public function save($data = [])
	{
		if (!Helpers\Can::administrate())
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

		$table = new Tables\Grids;

		return $table->save($data) ? $table->id : false;
	}
}
