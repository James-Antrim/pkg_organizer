<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Fields;

use Organizer\Helpers;

/**
 * Class creates a select box for (subject) pools.
 */
class GridsField extends OptionsField
{
	/**
	 * @var  string
	 */
	protected $type = 'Grids';

	/**
	 * Method to get the field input markup for a generic list.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput(): string
	{
		if (empty($this->value) and $campusID = Helpers\Input::getParams()->get('campusID'))
		{
			$this->value = Helpers\Campuses::getGridID($campusID);
		}

		return parent::getInput();
	}

	/**
	 * Returns an array of pool options
	 *
	 * @return array  the pool options
	 */
	protected function getOptions(): array
	{
		$options  = parent::getOptions();
		$campuses = Helpers\Grids::getOptions();

		return array_merge($options, $campuses);
	}
}
