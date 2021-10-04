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

use Joomla\CMS\Form\Form;
use Organizer\Helpers;
use Organizer\Helpers\Input;

/**
 * Class retrieves information for a filtered set of instances.
 */
class Export extends FormModel
{

	/**
	 * @inheritDoc
	 */
	public function __construct($config = [])
	{
		// Resolve potential inconsistencies cause by user choices before the form is initialized.
		if ($task = Input::getTask() and $task === 'export.reset')
		{
			// The language should not be reset with the rest.
			$form = ['languageTag' => Helpers\Languages::getTag()];
		}
		else
		{
			$fields = ['categoryID' => 0, 'groupID' => 0, 'organizationID' => 0, 'personID' => 0, 'roomID' => 0];
			$form   = Input::getArray();

			if (!empty($form['my']))
			{
				foreach (array_keys($fields) as $field)
				{
					unset($form[$field]);
				}
			}
			else
			{
				$categoryID     = empty($form['categoryID']) ? 0 : $form['categoryID'];
				$organizationID = empty($form['organizationID']) ? 0 : $form['organizationID'];
				$groupID        = empty($form['groupID']) ? 0 : $form['groupID'];
				$personID       = empty($form['personID']) ? 0 : $form['personID'];
				if ($organizationID)
				{
					if ($categoryID and !in_array($organizationID, Helpers\Categories::getOrganizationIDs($categoryID)))
					{
						$categoryID = 0;
						unset($form['categoryID']);
					}

					if ($groupID and !in_array($organizationID, Helpers\Groups::getOrganizationIDs($groupID)))
					{
						$groupID = 0;
						unset($form['groupID']);
					}

					if ($personID and !in_array($organizationID, Helpers\Persons::getOrganizationIDs($personID)))
					{
						unset($form['groupID']);
					}
				}

				if ($categoryID and $groupID and $categoryID !== Helpers\Groups::getCategory($groupID)->id)
				{
					unset($form['groupID']);
				}
			}
		}

		// Post: where the data was actually transmitted
		Input::set('jform', $form, 'post');

		// Data: where Joomla preemptively aggregates request information
		Input::set('jform', $form);

		parent::__construct($config);
	}

	/**
	 * Provides a strict access check which can be overwritten by extending classes.
	 *
	 * @return void performs error management via redirects as appropriate
	 */
	protected function authorize()
	{
		// Form has public access
	}

	/**
	 * @inheritDoc
	 */
	protected function filterForm(Form $form)
	{
		if (!Helpers\Users::getID())
		{
			$form->removeField('personID');
			$form->removeField('my');
		}

		if (Input::getBool('my'))
		{
			//$form->removeField('campusID');
			$form->removeField('organizationID');
			$form->removeField('categoryID');
			$form->removeField('groupID');
			$form->removeField('personID');
			$form->removeField('roomID');
		}
		elseif (!Input::getInt('organizationID') and !Input::getInt('categoryID'))
		{
			$form->removeField('groupID');
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getForm($data = [], $loadData = true)
	{
		return parent::getForm($data, $loadData);
	}

	/**
	 * @inheritdoc
	 */
	protected function loadFormData(): array
	{
		return Input::getArray();
	}
}