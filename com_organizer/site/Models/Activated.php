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

trait Activated
{
	/**
	 * Method to auto-populate the model state.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return void populates state properties
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState($ordering, $direction);

		$app     = Helpers\OrganizerHelper::getApplication();
		$filters = $app->getUserStateFromRequest($this->context . '.filter', 'filter', [], 'array');

		if (!array_key_exists('active', $filters) or $filters['active'] === '')
		{
			$this->setState('filter.active', 1);
		}
	}
}