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
 * Class retrieves information for a filtered set of subjects.
 */
class Subjects extends ListModel
{
	protected $filter_fields = [
		'organizationID' => 'organizationID',
		'personID'       => 'personID',
		'poolID'         => 'poolID',
		'programID'      => 'programID'
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
		$params = Helpers\Input::getParams();
		if (!empty($params->get('programID')) or !empty($this->state->get('calledPoolID')))
		{
			$form->removeField('organizationID', 'filter');
			$form->removeField('limit', 'list');
			$form->removeField('programID', 'filter');
			unset($this->filter_fields['organizationID'], $this->filter_fields['programID']);
		}
		elseif ($this->clientContext === self::BACKEND)
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
	 * Method to get an array of data items.
	 *
	 * @return  array  item objects on success, otherwise empty
	 */
	public function getItems()
	{
		$items = parent::getItems();

		if (empty($items))
		{
			return [];
		}

		foreach ($items as $item)
		{
			$item->persons = Helpers\Subjects::getPersons($item->id);
		}

		return $items;
	}

	/**
	 * Method to get a list of resources from the database.
	 *
	 * @return JDatabaseQuery  the query object
	 */
	protected function getListQuery()
	{
		$tag = Helpers\Languages::getTag();

		// Create the sql query
		$query = $this->_db->getQuery(true);
		$query->select("DISTINCT s.id, s.code, s.fullName_$tag AS name, s.fieldID, s.creditpoints")
			->from('#__organizer_subjects AS s');

		$searchFields = [
			's.fullName_de',
			's.shortName_de',
			's.abbreviation_de',
			's.fullName_en',
			's.shortName_en',
			's.abbreviation_en',
			's.code',
			's.lsfID'
		];

		$this->setOrganizationFilter($query, 'subject', 's');
		$this->setSearchFilter($query, $searchFields);

		$programID = $this->state->get('filter.programID', '');
		Helpers\Subjects::setProgramFilter($query, $programID, 'subject');

		// The selected pool supercedes any original called pool
		if ($poolID = $this->state->get('filter.poolID', ''))
		{
			Helpers\Subjects::setPoolFilter($query, $poolID);
		}
		elseif ($calledPoolID = $this->state->get('calledPoolID', ''))
		{
			Helpers\Subjects::setPoolFilter($query, $calledPoolID);
		}

		$personID = $this->state->get('filter.personID', '');
		if (!empty($personID))
		{
			if ($personID === '-1')
			{
				$query->leftJoin('#__organizer_subject_persons AS sp ON sp.subjectID = s.id')
					->where('sp.subjectID IS NULL');
			}
			else
			{
				$query->innerJoin('#__organizer_subject_persons AS sp ON sp.subjectID = s.id')
					->where("sp.personID = $personID");
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
		else
		{
			$programID = Helpers\Input::getParams()->get('programID');
			if ($poolID = Helpers\Input::getInput()->get->getInt('poolID', 0) or $programID)
			{
				if ($poolID)
				{
					$this->state->set('calledPoolID', $poolID);
				}
				else
				{
					$this->state->set('filter.programID', $programID);
				}
				$this->state->set('list.limit', 0);
			}
		}

		return;
	}
}
