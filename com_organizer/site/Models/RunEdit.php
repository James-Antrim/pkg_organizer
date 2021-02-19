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
 * Class loads a form for editing run data.
 */
class RunEdit extends EditModel
{
	/**
	 * Checks user authorization and initiates redirects accordingly.
	 *
	 * @return void
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
	 * @inheritDoc
	 */
	public function getForm($data = [], $loadData = true)
	{
		if ($form = parent::getForm($data, $loadData))
		{
			$defaultID = Helpers\Input::getFilterID('term', Helpers\Terms::getCurrentID());
			$form->setValue('termID', null, $form->getValue('termID', null, $defaultID));
		}

		return $form;
	}

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
	public function getTable($name = '', $prefix = '', $options = []): Tables\Runs
	{
		return new Tables\Runs();
	}
}
