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
use Organizer\Helpers\Languages;

/**
 * Class retrieves information for a filtered set of (degree) programs.
 */
class Programs extends ListModel
{
	protected $filter_fields = [
		'accredited'     => 'accredited',
		'degreeID'       => 'degreeID',
		'frequencyID'    => 'frequencyID',
		'organizationID' => 'organizationID'
	];

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

		if ($this->clientContext === self::BACKEND)
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
		$tag        = Languages::getTag();
		$query      = $this->_db->getQuery(true);
		$parts      = ["p.name_$tag", "' ('", 'd.abbreviation', "' '", 'p.accredited', "')'"];
		$nameClause = $query->concatenate($parts, '') . ' AS name';
		$query->select("DISTINCT p.id AS id, p.name_$tag AS name, d.abbreviation AS degree, p.accredited")
			->from('#__organizer_programs AS p')
			->innerJoin('#__organizer_degrees AS d ON d.id = p.degreeID');

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

		if ($this->clientContext === self::BACKEND)
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
