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
 * Class retrieves information for a filtered set of fields (of expertise).
 */
class FieldColors extends ListModel
{
	protected $defaultOrdering = 'field';

	protected $filter_fields = ['colorID' => 'colorID', 'organizationID' => 'organizationID'];

	/**
	 * Filters out form inputs which should not be displayed due to menu settings.
	 *
	 * @param   Form  $form  the form to be filtered
	 *
	 * @return void modifies $form
	 */
	protected function filterFilterForm(&$form)
	{
		if (count(Helpers\Can::documentTheseOrganizations()) === 1)
		{
			$form->removeField('organizationID', 'filter');
			unset($this->filter_fields['organizationID']);

			return;
		}
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

		$query->select("DISTINCT fc.id, fc.*")
			->select("c.name_$tag AS color")
			->select("f.name_$tag AS field")
			->select("o.shortName_$tag AS organization")
			->from('#__organizer_field_colors AS fc')
			->innerJoin('#__organizer_colors AS c ON c.id = fc.colorID')
			->innerJoin('#__organizer_fields AS f ON f.id = fc.fieldID')
			->innerJoin('#__organizer_organizations AS o ON o.id = fc.organizationID');

		// Explicitly set via request
		$this->setIDFilter($query, 'c.id', 'filter.colorID');
		$this->setIDFilter($query, 'f.id', 'filter.fieldID');

		// Explicitly set via request or implicitly set by authorization
		if ($organizationID = $this->state->get('filter.organizationID'))
		{
			$query->where("organizationID = $organizationID");
		}

		$this->setOrdering($query);

		return $query;
	}

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

		$accessibleOrganizations = Helpers\Can::documentTheseOrganizations();

		if (count($accessibleOrganizations) === 1)
		{
			$this->setState('filter.organizationID', $accessibleOrganizations[0]);
		}
	}
}
