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

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored subject data.
 */
class Subject extends CurriculumResource
{
	const COORDINATES = 1;

	const TEACHES = 2;

	/**
	 * Adds a prerequisite association. No access checks => this is not directly accessible and requires differing
	 * checks according to its calling context.
	 *
	 * @param   int    $subjectID       the id of the subject
	 * @param   array  $prerequisiteID  the id of the prerequisite
	 *
	 * @return bool  true on success, otherwise false
	 */
	private function addPrerequisite($subjectID, $prerequisiteID)
	{
		$query = $this->_db->getQuery(true);
		$query->insert('#__organizer_prerequisites')->columns('subjectID, prerequisiteID');
		$query->values("'$subjectID', '$prerequisiteID'");
		$this->_db->setQuery($query);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Adds a Subject => Event association. No access checks => this is not directly accessible and requires
	 * differing checks according to its calling context.
	 *
	 * @param   int    $subjectID  the id of the subject
	 * @param   array  $courseIDs  the id of the planSubject
	 *
	 * @return bool  true on success, otherwise false
	 */
	private function addSubjectMappings($subjectID, $courseIDs)
	{
		$query = $this->_db->getQuery(true);
		$query->insert('#__organizer_subject_mappings')->columns('subjectID, courseID');
		foreach ($courseIDs as $courseID)
		{
			$query->values("'$subjectID', '$courseID'");
		}

		$this->_db->setQuery($query);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Adds a person association. No access checks => this is not directly accessible and requires differing checks
	 * according to its calling context.
	 *
	 * @param   int    $subjectID  the id of the subject
	 * @param   array  $personID   the id of the person
	 * @param   int    $role       the person's role for the subject
	 *
	 * @return bool  true on success, otherwise false
	 */
	public function addPerson($subjectID, $personID, $role)
	{
		$query = $this->_db->getQuery(true);
		$query->insert('#__organizer_subject_persons')->columns('subjectID, personID, role');
		$query->values("$subjectID, $personID, $role");
		$this->_db->setQuery($query);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Checks if the property should be displayed. Setting it to NULL if not.
	 *
	 * @param   array  &$data      the form data
	 * @param   string  $property  the property name
	 *
	 * @return void  can change the &$data value at the property name index
	 */
	private function cleanStarProperty(&$data, $property)
	{
		if (!isset($data[$property]))
		{
			return;
		}

		if ($data[$property] == '-1')
		{
			$data[$property] = 'NULL';
		}
	}

	/**
	 * Attempts to delete the selected subject entries and related mappings
	 *
	 * @return boolean true on success, otherwise false
	 * @throws Exception => unauthorized access
	 */
	public function delete()
	{
		if (!Helpers\Can::documentTheseOrganizations())
		{
			throw new Exception(Languages::_('ORGANIZER_403'), 403);
		}

		if ($subjectIDs = Helpers\Input::getSelectedIDs())
		{
			foreach ($subjectIDs as $subjectID)
			{
				if (!Helpers\Can::document('subject', $subjectID))
				{
					throw new Exception(Languages::_('ORGANIZER_403'), 403);
				}

				if (!$this->deleteSingle($subjectID))
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Deletes mappings of a specific resource.
	 *
	 * @param   int  $resourceID  the id of the mapping
	 *
	 * @return boolean true on success, otherwise false
	 */
	protected function deleteRanges($resourceID)
	{
		if ($rangeIDs = Helpers\Subjects::getRangeIDs($resourceID))
		{
			foreach ($rangeIDs as $rangeID)
			{
				$success = $this->deleteRange($rangeID);
				if (!$success)
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Deletes a single curriculum resource.
	 *
	 * @param   int  $resourceID  the resource id
	 *
	 * @return boolean  true on success, otherwise false
	 */
	public function deleteSingle($resourceID)
	{
		if (!$this->deleteRanges($resourceID))
		{
			return false;
		}

		$table = new Tables\Subjects;

		return $table->delete($resourceID);
	}

	/**
	 * Returns the resource's existing ordering in the context of its parent.
	 *
	 * @param   int  $parentID    the parent id (curricula)
	 * @param   int  $resourceID  the resource id (resource table)
	 *
	 * @return mixed int if the resource has an existing ordering, otherwise null
	 */
	public function getExistingOrdering($parentID, $resourceID)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('ordering')
			->from('#__organizer_curricula')
			->where("parentID = '$parentID'")
			->where("subjectID = '$resourceID'");
		$dbo->setQuery($query);

		return Helpers\OrganizerHelper::executeQuery('loadResult', null);
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Table A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Subjects;
	}

	/**
	 * Adds a subject from LSF to the mappings table
	 *
	 * @param   int     $parentID   the id of the parent mapping
	 * @param   object &$XMLObject  the object representing the LSF pool
	 *
	 * @return boolean  true if the mapping exists, otherwise false
	 */
	public function import($parentID, &$XMLObject)
	{
		$lsfID = (string) (empty($XMLObject->modulid) ? $XMLObject->pordid : $XMLObject->modulid);
		$blocked = !empty($XMLObject->sperrmh) and strtolower((string) $XMLObject->sperrmh) == 'x';
		$invalidTitle = Helpers\LSF::invalidTitle($XMLObject);
		$subjects     = new Tables\Subjects;

		if (!$subjects->load(['lsfID' => $lsfID]))
		{
			if ($blocked or $invalidTitle)
			{
				return true;
			}

			Helpers\OrganizerHelper::message('ORGANIZER_SUBJECT_IMPORT_FAIL', 'error');

			return false;
		}

		$curricula = new Tables\Curricula;

		if ($curricula->load(['parentID' => $parentID, 'subjectID' => $subjects->id]))
		{
			if ($blocked or $invalidTitle)
			{
				return $this->deleteRange($curricula->id);
			}

			return true;
		}

		$range              = [];
		$range['parentID']  = $parentID;
		$range['subjectID'] = $subjects->id;
		$range['ordering']  = $this->getOrdering($parentID, $subjects->id, 'subject');
		$subjectAdded       = $this->addSubject($range);

		if (!$subjectAdded)
		{
			Helpers\OrganizerHelper::message('ORGANIZER_SUBJECT_ADD_FAIL', 'error');

			return false;
		}

		return true;
	}

	/**
	 * Processes the subject pre- & postrequisites selected for the subject
	 *
	 * @param   array &$data  the post data
	 *
	 * @return bool  true on success, otherwise false
	 */
	private function processFormPrerequisites(&$data)
	{
		if (!isset($data['prerequisites']) and !isset($data['postrequisites']))
		{
			return true;
		}

		$subjectID = $data['id'];

		if (!$this->removePrerequisites($subjectID))
		{
			return false;
		}

		if (!empty($data['prerequisites']))
		{
			foreach ($data['prerequisites'] as $prerequisiteID)
			{
				if (!$this->addPrerequisite($subjectID, $prerequisiteID))
				{
					return false;
				}
			}
		}

		if (!empty($data['postrequisites']))
		{
			foreach ($data['postrequisites'] as $postrequisiteID)
			{
				if (!$this->addPrerequisite($postrequisiteID, $subjectID))
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Processes the subject mappings selected for the subject
	 *
	 * @param   array &$data  the post data
	 *
	 * @return bool  true on success, otherwise false
	 */
	private function processFormSubjectMappings(&$data)
	{
		/*if (!isset($data['courseIDs']))
		{
			return true;
		}

		$subjectID = $data['id'];

		if (!$this->removeSubjectMappings($subjectID))
		{
			return false;
		}
		if (!empty($data['planSubjectIDs']))
		{
			if (!$this->addSubjectMappings($subjectID, $data['courseIDs']))
			{
				return false;
			}
		}*/

		return true;
	}

	/**
	 * Processes the persons selected for the subject
	 *
	 * @param   array &$data  the post data
	 *
	 * @return bool  true on success, otherwise false
	 */
	private function processFormPersons(&$data)
	{
		if (!isset($data['coordinators']) and !isset($data['persons']))
		{
			return true;
		}

		$subjectID = $data['id'];

		if (!$this->removePersons($subjectID))
		{
			return false;
		}

		$coordinators = array_filter($data['coordinators']);
		if (!empty($coordinators))
		{
			foreach ($coordinators as $coordinatorID)
			{
				if (!$this->addPerson($subjectID, $coordinatorID, self::COORDINATES))
				{
					return false;
				}
			}
		}

		$persons = array_filter($data['persons']);
		if (!empty($persons))
		{
			foreach ($persons as $personID)
			{
				if (!$this->addPerson($subjectID, $personID, self::TEACHES))
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Removes pre- & postrequisite associations for the given subject. No access checks => this is not directly
	 * accessible and requires differing checks according to its calling context.
	 *
	 * @param   int  $subjectID  the subject id
	 *
	 * @return boolean
	 */
	private function removePrerequisites($subjectID)
	{
		$query = $this->_db->getQuery(true);
		$query->delete('#__organizer_prerequisites')
			->where("subjectID = '$subjectID' OR prerequisiteID ='$subjectID'");
		$this->_db->setQuery($query);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Removes planSubject associations for the given subject. No access checks => this is not directly accessible and
	 * requires differing checks according to its calling context.
	 *
	 * @param   int  $subjectID  the subject id
	 *
	 * @return boolean
	 */
	/*private function removeSubjectMappings($subjectID)
	{
		$query = $this->_db->getQuery(true);
		$query->delete('#__organizer_subject_mappings')->where("subjectID = '$subjectID'");
		$this->_db->setQuery($query);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute');
	}*/

	/**
	 * Removes person associations for the given subject and role. No access checks => this is not directly
	 * accessible and requires differing checks according to its calling context.
	 *
	 * @param   int  $subjectID  the subject id
	 * @param   int  $role       the person role
	 *
	 * @return boolean
	 */
	public function removePersons($subjectID, $role = null)
	{
		$query = $this->_db->getQuery(true);
		$query->delete('#__organizer_subject_persons')->where("subjectID = '$subjectID'");
		if (!empty($role))
		{
			$query->where("role = $role");
		}

		$this->_db->setQuery($query);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Attempts to save the resource.
	 *
	 * @param   array  $data  form data which has been preprocessed by inheriting classes.
	 *
	 * @return mixed int id of the resource on success, otherwise boolean false
	 * @throws Exception => unauthorized access
	 */
	public function save($data = [])
	{
		$data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;

		if (empty($data['id']))
		{
			if (!Helpers\Can::documentTheseOrganizations())
			{
				throw new Exception(Languages::_('ORGANIZER_403'), 403);
			}
		}
		elseif (is_numeric($data['id']))
		{
			if (!Helpers\Can::document('subject', $data['id']))
			{
				throw new Exception(Languages::_('ORGANIZER_403'), 403);
			}
		}
		else
		{
			throw new Exception(Languages::_('ORGANIZER_400'), 400);
		}

		// Prepare the data
		$data['creditpoints'] = (float) $data['creditpoints'];

		$starProperties = ['expertise', 'selfCompetence', 'methodCompetence', 'socialCompetence'];
		foreach ($starProperties as $property)
		{
			$this->cleanStarProperty($data, $property);
		}

		$table = new Tables\Subjects;

		if (!$table->save($data))
		{
			return false;
		}

		$data['id'] = $table->id;

		if (!$this->processFormPersons($data))
		{
			return false;
		}

		if (!$this->processFormSubjectMappings($data))
		{
			return false;
		}

		if (!$this->processFormPrerequisites($data))
		{
			return false;
		}

		if (!$this->deleteRanges($data['id']))
		{
			return false;
		}

		if (!$this->saveCurriculum($data))
		{
			return false;
		}

		return $table->id;
	}

	/**
	 * Saves the resource's curriculum information.
	 *
	 * @param   array  $data  the data from the form
	 *
	 * @return bool true on success, otherwise false
	 */
	protected function saveCurriculum($data)
	{
		$range = ['subjectID' => $data['id']];

		foreach ($data['parentID'] as $parentID)
		{
			$range['parentID'] = $parentID;
			$range['ordering'] = $this->getOrdering($parentID, $range['subjectID']);

			if (!$this->addRange($range))
			{
				Helpers\OrganizerHelper::message('ORGANIZER_SUBJECT_ADD_FAIL', 'error');

				return false;
			}
		}

		return true;
	}
}
