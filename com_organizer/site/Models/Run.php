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
use Organizer\Tables\Runs as Table;

/**
 * Class which manages stored run data.
 */
class Run extends BaseModel
{
	/**
	 * @inheritDoc
	 */
	protected function authorize()
	{
		if (Helpers\Can::administrate())
		{
			return;
		}

		if (!Helpers\Can::scheduleTheseOrganizations() or Helpers\Input::getID())
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
	 * @return Table A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = []): Table
	{
		return new Table();
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
		$this->authorize();

		$data    = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;
		$endDate = '';
		$index   = 1;
		$runs    = [];

		foreach ($data['run'] as $row)
		{
			$endDate      = $endDate < $row['endDate'] ? $row['endDate'] : $endDate;
			$runs[$index] = $row;
			++$index;
		}

		$data['endDate'] = $endDate;
		$run             = ['runs' => $runs];
		$data['run']     = json_encode($run, JSON_UNESCAPED_UNICODE);

		$table = new Table();

		return $table->save($data) ? $table->id : false;
	}
}
