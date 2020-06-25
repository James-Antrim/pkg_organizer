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
 * Class loads a form for editing (subject) pool data.
 */
class PoolEdit extends EditModel
{
	/**
	 * Checks for user authorization to access the view.
	 *
	 * @return bool  true if the user can access the edit view, otherwise false
	 */
	public function allow()
	{
		if ($poolID = $this->item->id)
		{
			return Helpers\Can::document('pool', $poolID);
		}

		return (bool) Helpers\Can::documentTheseOrganizations();
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Pools A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Pools;
	}
}
