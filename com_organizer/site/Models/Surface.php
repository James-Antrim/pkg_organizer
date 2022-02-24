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
 * Class which manages stored din nrf data.
 */
class Surface extends BaseModel
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
	 * @return Tables\Surfaces A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = []): Tables\Surfaces
	{
		return new Tables\Surfaces();
	}

	/**
	 * Attempts to save the resource.
	 *
	 * @param   array  $data  the data from the form
	 *
	 * @return int|bool int id of the resource on success, otherwise bool false
	 * @throws Exception
	 */
	public function save($data = [])
	{
		$this->authorize();

		$data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;

		$invalidCode = (!isset($data['code']) or !is_numeric($data['code']));
		$invalidNDE  = (!isset($data['name_de']) or !Helpers\Input::filter($data['name_de']));
		$invalidNEN  = (!isset($data['name_en']) or !Helpers\Input::filter($data['name_en']));

		if ($invalidCode or $invalidNDE or $invalidNEN)
		{
			Helpers\OrganizerHelper::message('ORGANIZER_400', 'error');

			return false;
		}

		$data['code'] = abs((int) $data['code']);

		switch (true)
		{
			case $data['code'] > 999:
				Helpers\OrganizerHelper::message('ORGANIZER_INVALID_DIN_CODE', 'error');

				return false;
			case $data['code'] < 100:
				$data['code']   = $data['code'] < 10 ? '00' . $data['code'] : '0' . $data['code'];
				$data['typeID'] = 10;
				break;
			case $data['code'] < 200:
				$data['typeID'] = 1;
				break;
			case $data['code'] < 300:
				$data['typeID'] = 2;
				break;
			case $data['code'] < 400:
				$data['typeID'] = 3;
				break;
			case $data['code'] < 500:
				$data['typeID'] = 4;
				break;
			case $data['code'] < 600:
				$data['typeID'] = 5;
				break;
			case $data['code'] < 700:
				$data['typeID'] = 6;
				break;
			case $data['code'] < 800:
				$data['typeID'] = 7;
				break;
			case $data['code'] < 900:
				$data['typeID'] = 8;
				break;
			default:
				$data['typeID'] = 9;
				break;
		}

		$table = $this->getTable();

		if (!empty($data['id']))
		{
			$table->load($data['id']);
		}

		$table->bind($data);

		return $table->store() ? $table->id : false;
	}
}
