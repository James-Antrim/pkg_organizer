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

use JDatabaseQuery;
use Joomla\CMS\Form\Form;
use Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of (degree) programs.
 */
class Programs extends ListModel
{
	use Activated;

	protected $filter_fields = ['accredited', 'degreeID', 'frequencyID', 'organizationID'];

	/**
	 * Filters out form inputs which should not be displayed due to menu settings.
	 *
	 * @param   Form  $form  the form to be filtered
	 *
	 * @return void modifies $form
	 */
	public function filterFilterForm(&$form)
	{
		parent::filterFilterForm($form);

		if ($this->adminContext === self::BACKEND)
		{
			if (count(Helpers\Can::documentTheseOrganizations()) === 1)
			{
				$form->removeField('organizationID', 'filter');
				unset($this->filter_fields['organizationID']);
			}
		}

		return;
	}

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery()
	{
		$query = Helpers\Programs::getQuery();

		$this->setActiveFilter($query, 'p');
		$this->setOrganizationFilter($query, 'program', 'p');

		$searchColumns = ['p.name_de', 'p.name_en', 'accredited', 'd.name', 'description_de', 'description_en'];
		$this->setSearchFilter($query, $searchColumns);

		$this->setValueFilters($query, ['degreeID', 'frequencyID', 'accredited']);

		$this->setOrdering($query);

		return $query;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return void
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState($ordering, $direction);

		if ($this->adminContext === self::BACKEND)
		{
			$authorized = Helpers\Can::documentTheseOrganizations();
			if (count($authorized) === 1)
			{
				$this->state->set('filter.organizationID', $authorized[0]);
			}
		}

		return;
	}
}
