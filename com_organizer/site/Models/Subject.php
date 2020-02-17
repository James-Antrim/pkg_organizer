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
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored subject data.
 */
class Subject extends CurriculumResource
{
	const COORDINATES = 1, TEACHES = 2;

	protected $resource = 'subject';

	/**
	 * Adds a Subject => Event association. No access checks => this is not directly accessible and requires
	 * differing checks according to its calling context.
	 *
	 * @param   int    $subjectID  the id of the subject
	 * @param   array  $eventIDs   the ids of the events
	 *
	 * @return bool  true on success, otherwise false
	 */
	private function addEvents($subjectID, $eventIDs)
	{
		$query = $this->_db->getQuery(true);
		$query->insert('#__organizer_subject_events')->columns('subjectID, eventID');

		foreach ($eventIDs as $eventID)
		{
			$query->values("'$subjectID', '$eventID'");
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
	 * Deletes ranges of a specific curriculum resource.
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
	 * @return Tables\Subjects A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Subjects;
	}

	/**
	 * Method to import data associated with resources from LSF
	 *
	 * @return bool true on success, otherwise false
	 */
	public function import()
	{
		$resourceIDs = Helpers\Input::getSelectedIDs();

		foreach ($resourceIDs as $subjectID)
		{
			if (!$this->importSingle($subjectID))
			{
				return false;
			}

			if (!$this->resolveDependencies($subjectID))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Method to import data associated with a subject from LSF
	 *
	 * @param   int  $subjectID  the id of the subject entry
	 *
	 * @return boolean  true on success, otherwise false
	 */
	public function importSingle($subjectID)
	{
		$table = new Tables\Subjects;

		if (!$table->load($subjectID) or empty($table->lsfID))
		{
			return false;
		}

		$client  = new Helpers\LSF;
		$subject = $client->getModuleByModulid($table->lsfID);

		// The system administrator does not wish to display entries with this value
		$invalid    = (empty($subject->modul) or empty($subject->modul->sperrmh));
		$blocked    = $invalid ? true : strtolower((string) $subject->modul->sperrmh) == 'x';
		$validTitle = $this->validTitle($subject, true);

		if ($blocked or !$validTitle)
		{
			return $this->deleteSingle($table->id);
		}

		if (!$this->setPersons($table->id, $subject))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_SAVE_FAIL', 'error');

			return false;
		}

		$this->setNameAttributes($table, $subject);

		Helpers\SubjectsLSF::processAttributes($table, $subject);

		return $table->store();
	}

	/**
	 * Checks for subjects with the given possible module number associated with to the same programs
	 *
	 * @param   array  $possibleModNos  the possible module numbers used in the attribute text
	 * @param   array  $programs        the programs whose curricula contain the subject
	 *
	 * @return array the subject information for subjects with dependencies
	 */
	private function parseDependencies($possibleModNos, $programs)
	{
		$select = 's.id AS subjectID, code, ';
		$select .= 'abbreviation_de, shortName_de, name_de, abbreviation_en, shortName_en, name_en, ';
		$select .= 'c.id AS curriculumID, m.lft, m.rgt, ';

		$query = $this->_db->getQuery(true);
		$query->from('#__organizer_subjects AS s')
			->innerJoin('#__organizer_curricula AS c ON c.subjectID = s.id');

		$subjects = [];
		foreach ($possibleModNos as $possibleModuleNumber)
		{
			$possibleModuleNumber = strtoupper($possibleModuleNumber);
			if (empty(preg_match('/[A-Z0-9]{3,10}/', $possibleModuleNumber)))
			{
				continue;
			}

			foreach ($programs as $program)
			{
				$query->clear('SELECT');
				$query->select($select . "'{$program['id']}' AS programID");

				$query->clear('where');
				$query->where("lft > '{$program['lft']}' AND rgt < '{$program['rgt']}'");
				$query->where("s.code = '$possibleModuleNumber'");
				$this->_db->setQuery($query);

				if (!$curriculumSubjects = Helpers\OrganizerHelper::executeQuery('loadAssocList', [], 'curriculumID'))
				{
					continue;
				}

				if (empty($subjects[$possibleModuleNumber]))
				{
					$subjects[$possibleModuleNumber] = $curriculumSubjects;
				}
				else
				{
					$subjects[$possibleModuleNumber] = $subjects[$possibleModuleNumber] + $curriculumSubjects;
				}
			}
		}

		return $subjects;
	}

	/**
	 * Processes the subject mappings selected for the subject
	 *
	 * @param   array &$data  the post data
	 *
	 * @return bool  true on success, otherwise false
	 */
	private function processEvents(&$data)
	{
		if (!isset($data['courseIDs']))
		{
			return true;
		}

		$subjectID = $data['id'];

		if (!$this->removeEvents($subjectID))
		{
			return false;
		}
		if (!empty($data['eventIDs']))
		{
			if (!$this->addEvents($subjectID, $data['eventIDs']))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Processes the persons selected for the subject
	 *
	 * @param   array &$data  the post data
	 *
	 * @return bool  true on success, otherwise false
	 */
	private function processPersons(&$data)
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
	 * Processes the subject pre- & postrequisites selected for the subject
	 *
	 * @param   array &$data  the post data
	 *
	 * @return bool  true on success, otherwise false
	 */
	private function processPrerequisites(&$data)
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
	 * Creates a resource and resource curriculum hierarchy as necessary.
	 *
	 * @param   object &$XMLObject       a SimpleXML object containing rudimentary resource data
	 * @param   int     $organizationID  the id of the organization with which the resource is associated
	 * @param   int     $parentID        the  id of the parent entry in the curricula table
	 *
	 * @return bool  true on success, otherwise false
	 */
	public function processResource(&$XMLObject, $organizationID, $parentID)
	{
		$lsfID = (string) (empty($XMLObject->modulid) ? $XMLObject->pordid : $XMLObject->modulid);
		if (empty($lsfID))
		{
			return false;
		}

		$blocked = !empty($XMLObject->sperrmh) and strtolower((string) $XMLObject->sperrmh) == 'x';
		$validTitle = $this->validTitle($XMLObject);

		$subjects = new Tables\Subjects;

		if (!$subjects->load(['lsfID' => $lsfID]))
		{
			// There isn't one and shouldn't be one
			if ($blocked or !$validTitle)
			{
				return true;
			}

			$subjects->organizationID = $organizationID;
			$subjects->lsfID          = $lsfID;

			if (!$subjects->store())
			{
				return false;
			}
		}
		elseif ($blocked or !$validTitle)
		{
			return $this->deleteSingle($subjects->id);
		}

		$curricula = new Tables\Curricula;

		if (!$curricula->load(['parentID' => $parentID, 'subjectID' => $subjects->id]))
		{
			$range = [
				'parentID'  => $parentID,
				'subjectID' => $subjects->id,
				'ordering'  => $this->getOrdering($parentID, $subjects->id)
			];

			if (!$this->shiftUp($parentID, $range['ordering']))
			{
				return false;
			}

			if (!$this->addRange($range))
			{
				return false;
			}

			$curricula->load(['parentID' => $parentID, 'poolID' => $subjects->id]);
		}

		return $this->importSingle($subjects->id);
	}

	/**
	 * Removes planSubject associations for the given subject. No access checks => this is not directly accessible and
	 * requires differing checks according to its calling context.
	 *
	 * @param   int  $subjectID  the subject id
	 *
	 * @return boolean
	 */
	private function removeEvents($subjectID)
	{
		$query = $this->_db->getQuery(true);
		$query->delete('#__organizer_subject_curricula')->where("subjectID = '$subjectID'");
		$this->_db->setQuery($query);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute');
	}

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
	 * Parses the prerequisites text and replaces subject references with links to the subjects
	 *
	 * @param   string  $subjectID  the id of the subject being processed
	 *
	 * @return bool true on success, otherwise false
	 */
	public function resolveDependencies($subjectID)
	{
		$table = new Tables\Subjects;

		// Entry doesn't exist. Should not occur.
		if (!$table->load($subjectID))
		{
			return true;
		}

		// Subject is not associated with a program
		if (!$programs = Helpers\Subjects::getPrograms($subjectID))
		{
			return true;
		}

		// Ordered by length for faster in case short is a subset of long.
		$checkedAttributes = [
			'code',
			'name_de',
			'shortName_de',
			'abbreviation_de',
			'name_en',
			'shortName_en',
			'abbreviation_en'
		];

		// Flag to be set should one of the attribute texts consist only of module information. => Text should be empty.
		$attributeChanged = false;

		$reqAttribs     = [
			'prerequisites_de' => 'pre',
			'prerequisites_en' => 'pre',
			'usedFor_de'       => 'post',
			'usedFor_en'       => 'post'
		];
		$postrequisites = [];
		$prerequisites  = [];

		foreach ($reqAttribs as $attribute => $direction)
		{
			$originalText   = $table->$attribute;
			$sanitizedText  = $this->sanitizeText($originalText);
			$possibleModNos = preg_split('[\ ]', $sanitizedText);

			if ($dependencies = $this->parseDependencies($possibleModNos, $programs))
			{
				// Aggregate potential dependencies across language specific attributes
				if ($direction === 'pre')
				{
					$prerequisites = $prerequisites + $dependencies;
				}
				else
				{
					$postrequisites = $postrequisites + $dependencies;
				}

				$emptyAttribute = Helpers\SubjectsLSF::checkContents($originalText, $checkedAttributes, $dependencies);

				if ($emptyAttribute)
				{
					$table->$attribute = '';
					$attributeChanged  = true;
				}
			}
		}

		if (!$this->saveDependencies($programs, $subjectID, $prerequisites, 'pre'))
		{
			return false;
		}

		if (!$this->saveDependencies($programs, $subjectID, $postrequisites, 'post'))
		{
			return false;
		}

		if ($attributeChanged)
		{
			return $table->store();
		}

		return true;
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

		if (!$this->processPersons($data))
		{
			return false;
		}

		if (!$this->processEvents($data))
		{
			return false;
		}

		if (!$this->processPrerequisites($data))
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
				return false;
			}
		}

		return true;
	}

	/**
	 * Saves the dependencies to the prerequisites table
	 *
	 * @param   array   $programs      the programs that the schedule should be associated with
	 * @param   int     $subjectID     the id of the subject being processed
	 * @param   array   $dependencies  the subject dependencies
	 * @param   string  $type          the type (direction) of dependency: pre|post
	 *
	 * @return bool
	 */
	private function saveDependencies($programs, $subjectID, $dependencies, $type)
	{
		foreach ($programs as $program)
		{
			$subjectIDs = Helpers\Programs::getSubjectIDs($program['id'], $subjectID);

			$dependencyIDs = [];
			foreach ($dependencies as $dependency)
			{
				foreach ($dependency as $curriculumID => $subjectData)
				{
					// A dependency is only relevant in the program context
					if ($subjectData['programID'] == $program['id'])
					{
						$dependencyIDs[$curriculumID] = $curriculumID;
					}
				}
			}

			if ($type == 'pre')
			{
				$success = $this->savePrerequisites($dependencyIDs, $subjectIDs);
			}
			else
			{
				$success = $this->savePrerequisites($subjectIDs, $dependencyIDs);
			}

			if (empty($success))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Saves the prerequisite relation.
	 *
	 * @param   array  $prerequisiteIDs  ids for prerequisite subject entries in the program curriculum context
	 * @param   array  $subjectIDs       ids for subject entries in the program curriculum context
	 *
	 * @return bool true on success otherwise false
	 */
	private function savePrerequisites($prerequisiteIDs, $subjectIDs)
	{
		// Delete any and all old prerequisites in case there are now fewer.
		if ($subjectIDs)
		{
			$deleteQuery = $this->_db->getQuery(true);
			$deleteQuery->delete('#__organizer_prerequisites')
				->where('subjectID IN (' . implode(',', $subjectIDs) . ')');
			$this->_db->setQuery($deleteQuery);
			Helpers\OrganizerHelper::executeQuery('execute');
		}

		foreach ($prerequisiteIDs as $prerequisiteID)
		{
			foreach ($subjectIDs as $subjectID)
			{
				$checkQuery = $this->_db->getQuery(true);
				$checkQuery->select('COUNT(*)')
					->from('#__organizer_prerequisites')
					->where("prerequisiteID = '$prerequisiteID'")
					->where("subjectID = '$subjectID'");
				$this->_db->setQuery($checkQuery);

				$entryExists = (bool) Helpers\OrganizerHelper::executeQuery('loadResult');

				if (!$entryExists)
				{
					$insertQuery = $this->_db->getQuery(true);
					$insertQuery->insert('#__organizer_prerequisites');
					$insertQuery->columns('prerequisiteID, subjectID');
					$insertQuery->values("'$prerequisiteID', '$subjectID'");
					$this->_db->setQuery($insertQuery);
					Helpers\OrganizerHelper::executeQuery('execute');
				}
			}
		}

		return true;
	}

	/**
	 * Creates an association between persons, subjects and their roles for that subject.
	 *
	 * @param   int     $subjectID   the id of the subject
	 * @param   object &$dataObject  an object containing the lsf response
	 *
	 * @return bool  true on success, otherwise false
	 */
	private function setPersons($subjectID, &$dataObject)
	{
		$coordinators = $dataObject->xpath('//verantwortliche');
		$persons      = $dataObject->xpath('//dozent');

		if (empty($coordinators) and empty($persons))
		{
			return true;
		}

		$roleSet = $this->setPersonsByRoles($subjectID, $coordinators, self::COORDINATES);
		if (!$roleSet)
		{
			return false;
		}

		$teachingSet = $this->setPersonsByRoles($subjectID, $persons, self::TEACHES);
		if (!$teachingSet)
		{
			return false;
		}

		return true;
	}

	/**
	 * Sets subject persons by their role for the subject
	 *
	 * @param   int    $subjectID  the subject's id
	 * @param   array &$persons    an array containing information about the subject's persons
	 * @param   int    $role       the person's role
	 *
	 * @return boolean  true on success, otherwise false
	 */
	private function setPersonsByRoles($subjectID, &$persons, $role)
	{
		$subjectModel = new Subject;
		$removed      = $subjectModel->removePersons($subjectID, $role);

		if (!$removed)
		{
			return false;
		}

		if (empty($persons))
		{
			return true;
		}

		$surnameAttribute  = $role == self::COORDINATES ? 'nachname' : 'personal.nachname';
		$forenameAttribute = $role == self::COORDINATES ? 'vorname' : 'personal.vorname';

		foreach ($persons as $person)
		{
			$personData             = [];
			$personData['surname']  = trim((string) $person->personinfo->$surnameAttribute);
			$personData['username'] = trim((string) $person->hgnr);

			if (empty($personData['surname']) or empty($personData['username']))
			{
				continue;
			}

			$loadCriteria           = [];
			$loadCriteria[]         = ['username' => $personData['username']];
			$personData['forename'] = (string) $person->personinfo->$forenameAttribute;

			if (!empty($personData['forename']))
			{
				$loadCriteria[] = ['surname' => $personData['surname'], 'forename' => $personData['forename']];
			}

			$personTable = new Tables\Persons;
			$loaded      = false;

			foreach ($loadCriteria as $criteria)
			{
				if ($personTable->load($criteria))
				{
					$loaded = true;
					break;
				}
			}

			if (!$loaded)
			{
				if (!$personTable->save($personData))
				{
					return false;
				}
			}

			if (!$subjectModel->addPerson($subjectID, $personTable->id, $role))
			{
				return false;
			}
		}

		return true;
	}
}
