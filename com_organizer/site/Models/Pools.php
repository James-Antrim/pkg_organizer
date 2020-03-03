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
 * Class retrieves information for a filtered set of (subject) pools.
 */
class Pools extends ListModel
{
	protected $filter_fields = [
		'organizationID' => 'organizationID',
		'fieldID'        => 'fieldID',
		'programID'      => 'programID'];

	/**
	 * Filters out form inputs which should not be displayed due to menu settings.
	 *
	 * @param   Form  $form  the form to be filtered
	 *
	 * @return void modifies $form
	 */
	public function filterFilterForm(&$form)
	{
		if (count(Helpers\Can::documentTheseOrganizations()) === 1)
		{
			$form->removeField('organizationID', 'filter');
			unset($this->filter_fields['organizationID']);
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
		$tag   = Helpers\Languages::getTag();
		$query = $this->_db->getQuery(true);

		$query->select("DISTINCT p.id, p.fullName_$tag AS name, p.fieldID")->from('#__organizer_pools AS p');

		$this->setOrganizationFilter($query, 'pool', 'p');

		$searchColumns = [
			'p.fullName_de',
			'p.shortName_de',
			'p.abbreviation_de',
			'p.fullName_en',
			'p.shortName_en',
			'p.abbreviation_en'
		];
		$this->setSearchFilter($query, $searchColumns);

		$this->setValueFilters($query, ['fieldID']);

		$programID = $this->state->get('filter.programID', '');
		Helpers\Pools::setProgramFilter($query, $programID, 'pool');

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

		$authorized = Helpers\Can::documentTheseOrganizations();
		if (count($authorized) === 1)
		{
			$this->state->set('filter.organizationID', $authorized[0]);
		}

		return;
	}
}
