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
	protected $defaultOrdering = 'name';

	protected $filter_fields = ['colorID', 'organizationID'];

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

		$query->select("DISTINCT fc.id, fc.colorID, c.name_$tag AS color, f.code, f.name_$tag AS name")
			->from('#__organizer_field_colors AS fc')
			->innerJoin('#__organizer_colors AS c ON c.id = fc.colorID')
			->innerJoin('#__organizer_fields AS f ON f.id = fc.fieldID')
			->group('f.id');

		$this->setSearchFilter($query, ['f.name_de', 'f.name_en', 'code']);

		$colorID        = Helpers\Input::getFilterID('color');
		$organizationID = $this->state->get('filter.organizationID');

		if ($colorID or $organizationID)
		{
			if ($colorID)
			{
				$query->where("colorID = $colorID");
			}

			if ($organizationID)
			{
				$query->where("organizationID = $organizationID");
			}
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
