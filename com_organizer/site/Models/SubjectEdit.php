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

use Organizer\Helpers\Can;
use Organizer\Tables\Subjects as SubjectsTable;

/**
 * Class loads a form for editing data.
 */
class SubjectEdit extends EditModel
{
	protected $association;

	/**
	 * Checks for user authorization to access the view
	 *
	 * @return bool  true if the user can access the view, otherwise false
	 */
	protected function allowEdit()
	{
		$subjectID = empty($this->item->id) ? 0 : $this->item->id;

		return Can::document('subject', $subjectID);
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return SubjectsTable A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new SubjectsTable;
	}
}
