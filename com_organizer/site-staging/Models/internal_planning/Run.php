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
 * Class which manages stored run data.
 */
class Run extends BaseModel
{
	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Runs A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Runs;
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
		if (!Helpers\Can::administrate())
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
		}

		$data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;

		$runs  = [];
		$index = 1;
		foreach ($data['run'] as $row)
		{
			$runs[$index] = $row;
			++$index;
		}

		$run         = ['runs' => $runs];
		$data['run'] = json_encode($run, JSON_UNESCAPED_UNICODE);

		$table = new RunsTable;

		return $table->save($data) ? $table->id : false;
	}
}
