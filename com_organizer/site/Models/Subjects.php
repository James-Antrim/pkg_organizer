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
		'instructionLanguage' => 'instructionLanguage',
		'organizationID'      => 'organizationID',
		'personID'            => 'personID',
		'poolID'              => 'poolID',
		'programID'           => 'programID'
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
		if (!empty($this->state->get('calledProgramID')) or !empty($this->state->get('calledPoolID')))
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

		if ($programID = $this->state->get('filter.programID', ''))
		{
			Helpers\Subjects::setProgramFilter($query, $programID, 'subject', 's');
		}

		// The selected pool supersedes any original called pool
		if ($poolID = $this->state->get('filter.poolID', ''))
		{
			Helpers\Subjects::setPoolFilter($query, $poolID, 's');
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

		$this->setValueFilters($query, ['instructionLanguage']);

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

		$calledPool    = 0;
		$calledProgram = 0;
		$poolID        = self::ALL;
		$programID     = self::ALL;

		if ($this->clientContext === self::BACKEND)
		{
			$authorized = Helpers\Can::documentTheseOrganizations();
			if (count($authorized) === 1)
			{
				$organizationID = $authorized[0];
				$this->state->set('filter.organizationID', $organizationID);
			}
			else
			{
				$organizationID = Helpers\Input::getFilterID('organization', self::ALL);
			}
		}
		else
		{
			$organizationID = Helpers\Input::getFilterID('organization', self::ALL);

			// Program ID can be set by menu settings or the request
			if ($programID = Helpers\Input::getInt('programID')
				or $programID = Helpers\Input::getParams()->get('programID', 0)
				or $programID = $this->state->get('calledProgramID'))
			{
				$calledProgram = $programID;
			}

			// Pool ID can be set by the request
			if ($poolID = Helpers\Input::getInt('poolID', 0)
				or $poolID = $this->state->get('calledPoolID'))
			{
				$calledPool = $poolID;
			}
		}

		$defaultPool = $calledPool ? $calledPool : self::ALL;
		$poolID      = $calledPool ? $poolID : Helpers\Input::getFilterID('pool', $defaultPool);
		$programID   = $calledProgram ? $programID : Helpers\Input::getFilterID('program', self::ALL);

		$this->state->set('calledPoolID', false);
		$this->state->set('calledProgramID', false);

		$this->state->set('filter.poolID', self::ALL);
		$this->state->set('filter.programID', self::ALL);

		// The existence of the organization id precludes called curriculum parameter use
		if ($organizationID)
		{
			// Pool and program filtering is completely disassociated subjects
			if ($organizationID === self::NONE)
			{
				return;
			}

			if ($programID)
			{
				// Disassociated subjects requested => precludes pool selections
				if ($programID === self::NONE)
				{
					$this->state->set('filter.programID', $programID);

					return;
				}

				// Selected program is incompatible with the selected organization => precludes pool selections
				if (!Helpers\Programs::isAssociated($organizationID, $programID))
				{
					return;
				}

			}

			if ($poolID)
			{
				if ($poolID === self::NONE)
				{
					$this->state->set('filter.poolID', $poolID);
					$this->state->set('filter.programID', $programID);

					return;
				}

				if (!Helpers\Pools::isAssociated($organizationID, $poolID))
				{
					$this->state->set('filter.poolID', self::ALL);
					$this->state->set('filter.programID', $programID);

					return;
				}

				if ($programID)
				{
					if (!$prRanges = Helpers\Programs::getRanges($programID))
					{
						return;
					}

					if (!$plRanges = Helpers\Pools::getRanges($poolID)
						or !Helpers\Subjects::poolInProgram($plRanges, $prRanges))
					{
						$this->state->set('filter.poolID', self::ALL);
						$this->state->set('filter.programID', $programID);

						return;
					}
				}
			}

			// Curriculum filters are either valid or empty
			$this->state->set('filter.poolID', $poolID);
			$this->state->set('filter.programID', $programID);

			return;
		}

		if ($programID === self::NONE)
		{
			$this->state->set('filter.programID', $programID);

			return;
		}

		if (!$poolID)
		{
			if ($calledProgram)
			{
				$this->state->set('calledProgramID', $calledProgram);
			}

			$this->state->set('filter.programID', $programID);

			return;
		}

		if ($poolID === self::NONE)
		{
			if ($programID)
			{
				if ($calledProgram)
				{
					$this->state->set('calledProgramID', $programID);
				}

				$this->state->set('filter.poolID', $poolID);
				$this->state->set('filter.programID', $programID);
			}
			else
			{
				$this->state->set('filter.poolID', self::ALL);
				$this->state->set('filter.programID', self::NONE);
			}

			return;
		}

		// The existence of a program id precludes the pool having been called directly
		if ($programID)
		{
			// None has already been eliminated => the chosen program is invalid => allow reset
			if (!$prRanges = Helpers\Programs::getRanges($programID))
			{
				return;
			}


			$this->state->set('filter.programID', $programID);

			// Pool is invalid or invalid for the chosen program context
			if (!$plRanges = Helpers\Pools::getRanges($poolID)
				or !Helpers\Subjects::poolInProgram($plRanges, $prRanges))
			{
				return;
			}

			if ($calledPool)
			{
				$this->state->set('calledPoolID', $calledPool);
			}
			elseif ($calledProgram)
			{
				$this->state->set('calledProgramID', $calledProgram);
			}

			$this->state->set('filter.poolID', $poolID);

			return;
		}

		if ($calledPool)
		{
			$this->state->set('calledPoolID', $calledPool);
		}

		$this->state->set('filter.poolID', $poolID);

		return;
	}
}
