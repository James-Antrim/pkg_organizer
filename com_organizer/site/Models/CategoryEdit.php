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
 * Class loads a form for editing category data.
 */
class CategoryEdit extends EditModel
{
	protected $association = 'program';

	/**
	 * Checks access to edit the resource.
	 *
	 * @return void
	 */
	public function authorize()
	{
		if (!Helpers\Can::edit('category', (int) $this->item->id))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return mixed    Object on success, false on failure.
	 */
	public function getItem($pk = null)
	{
		$this->item                 = parent::getItem($pk);
		$this->item->organizationID = Helpers\Categories::getOrganizationIDs($this->item->id);

		return $this->item;
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Categories A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Categories;
	}
}
