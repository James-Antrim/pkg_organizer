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
 * Class loads a form for editing field (of expertise) data.
 */
class FieldColorEdit extends EditModel
{
	/**
	 * Checks access to edit the resource.
	 *
	 * @return void
	 */
	public function authorize()
	{
		if (!Helpers\Users::getUser())
		{
			Helpers\OrganizerHelper::error(401);
		}

		if ($fcID = Helpers\Input::getID() and Helpers\Can::document('fieldcolor', $fcID)
			or Helpers\Can::documentTheseOrganizations())
		{
			return;
		}

		Helpers\OrganizerHelper::error(403);
	}


	/**
	 * Method to get the form
	 *
	 * @param   array  $data      Data         (default: array)
	 * @param   bool   $loadData  Load data  (default: true)
	 *
	 * @return mixed Form object on success, False on error.
	 */
	public function getForm($data = [], $loadData = true)
	{
		if (!$form = parent::getForm($data, $loadData))
		{
			return false;
		}

		if ($fcID = Helpers\Input::getID())
		{
			$form->setFieldAttribute('fieldID', 'disabled', true);
			$form->setFieldAttribute('organizationID', 'disabled', true);
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
	 * @return Tables\FieldColors A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\FieldColors;
	}
}
