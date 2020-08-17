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
use Organizer\Helpers;
use Organizer\Helpers\OrganizerHelper;
use Organizer\Tables;
use SimpleXMLElement;

/**
 * Class which manages stored subject data.
 */
class Subject extends CurriculumResource
{
	use Associated, SubOrdinate;

	const COORDINATES = 1, TEACHES = 2;

	protected $helper = 'Subjects';

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
	/*private function addEvents($subjectID, $eventIDs)
	{
		$query = $this->_db->getQuery(true);
		$query->insert('#__organizer_subject_events')->columns('subjectID, eventID');

		foreach ($eventIDs as $eventID)
		{
			$query->values("'$subjectID', '$eventID'");
		}

		$this->_db->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('execute');
	}*/

	/**
	 * Associates subject curriculum dependencies.
	 *
	 * @param   array  $programRanges       the program ranges
	 * @param   array  $prerequisiteRanges  the prerequisite ranges
	 * @param   array  $subjectRanges       the subject ranges
	 * @param   bool   $pre                 whether or not the function is being called in the prerequisite context this
	 *                                      influences how possible deprecated entries are detected.
	 *
	 * @return bool true on success, otherwise false
	 */
	private function associateDependencies($programRanges, $prerequisiteRanges, $subjectRanges, $pre)
	{
		foreach ($programRanges as $programRange)
		{
			if (!$rprRanges = $this->filterRanges($programRange, $prerequisiteRanges))
			{
				continue;
			}

			if (!$rsRanges = $this->filterRanges($programRange, $subjectRanges))
			{
				continue;
			}

			// Remove deprecated associations
			$rprIDs = implode(',', Helpers\Subjects::filterIDs($rprRanges));
			$rsIDs  = implode(',', Helpers\Subjects::filterIDs($rsRanges));
			$query  = $this->_db->getQuery(true);
			$query->delete('#__organizer_prerequisites');

			if ($pre)
			{
				$query->where("subjectID IN ($rsIDs)")->where("prerequisiteID NOT IN ($rprIDs)");
			}
			else
			{
				$query->where("prerequisiteID IN ($rsIDs)")->where("subjectID NOT IN ($rprIDs)");
			}

			$this->_db->setQuery($query);

			if (!OrganizerHelper::executeQuery('execute'))
			{
				return false;
			}

			foreach ($rprRanges as $rprRange)
			{
				foreach ($rsRanges as $rsRange)
				{
					$data          = ['subjectID' => $rsRange['id'], 'prerequisiteID' => $rprRange['id']];
					$prerequisites = new Tables\Prerequisites();

					if ($prerequisites->load($data))
					{
						continue;
					}

					if (!$prerequisites->save($data))
					{
						return false;
					}
				}
			}
		}

		return true;
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
	 * Filters subject ranges to those relevant to a given program range.
	 *
	 * @param   array  $programRange   the program range being iterated
	 * @param   array  $subjectRanges  the ranges for the given subject
	 *
	 * @return array the relevant subject ranges
	 */
	private function filterRanges($programRange, $subjectRanges)
	{
		$left           = $programRange['lft'];
		$relevantRanges = [];
		$right          = $programRange['rgt'];

		foreach ($subjectRanges as $subjectRange)
		{
			if ($subjectRange['lft'] > $left and $subjectRange['rgt'] < $right)
			{
				$relevantRanges[] = $subjectRange;
			}
		}

		return $relevantRanges;
	}

	/**
	 * Method to import data associated with a subject from LSF. Import requires an existing subject entry with its
	 * LSFID set. It does not alter curriculum mappings.
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

		$client   = new Helpers\LSF;
		$response = $client->getModuleByModulid($table->lsfID);

		// Invalid response
		if (empty($response->modul))
		{
			return $this->deleteSingle($table->id);
		}

		$subject = $response->modul;

		// Suppressed
		if (!empty($subject->sperrmh) and strtolower((string) $subject->sperrmh) === 'x')
		{
			OrganizerHelper::message('ORGANIZER_SUBJECT_SUPPRESSED', 'notice');

			return $this->deleteSingle($table->id);
		}

		if (!$this->validTitle($subject))
		{
			OrganizerHelper::message('ORGANIZER_IMPORT_TITLE_INVALID', 'error');

			return $this->deleteSingle($table->id);
		}

		if (!$this->setPersons($table->id, $subject))
		{
			OrganizerHelper::message('ORGANIZER_SAVE_FAIL', 'error');

			return false;
		}

		$this->setNameAttributes($table, $subject);

		Helpers\SubjectsLSF::processAttributes($table, $subject);

		if (!$table->store())
		{
			return false;
		}

		return $this->resolveTextDependencies($table->id);
	}

	/**
	 * Processes the events to be associated with the subject
	 *
	 * @param   array &$data  the post data
	 *
	 * @return bool  true on success, otherwise false
	 */
	/*private function processEvents(&$data)
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
	}*/

	/**
	 * Processes the persons selected for the subject
	 *
	 * @param   array  $data  the post data
	 *
	 * @return bool  true on success, otherwise false
	 */
	private function processPersons($data)
	{
		// More efficient to remove all subject persons associations for the subject than iterate the persons table
		if (!$this->removePersons($data['id']))
		{
			return false;
		}

		$coordinatorsSet = !empty($data['coordinators']);
		$personsSet      = !empty($data['persons']);

		if (!$coordinatorsSet and !$personsSet)
		{
			return true;
		}

		if ($coordinatorsSet and $persons = array_filter($data['coordinators']))
		{
			foreach ($persons as $personID)
			{
				$spData = ['personID' => $personID, 'role' => self::COORDINATES, 'subjectID' => $data['id']];
				$table  = new Tables\SubjectPersons;

				if (!$table->save($spData))
				{
					return false;
				}
			}

		}

		if ($personsSet and $persons = array_filter($data['persons']))
		{
			foreach ($persons as $personID)
			{
				$spData = ['personID' => $personID, 'role' => self::TEACHES, 'subjectID' => $data['id']];
				$table  = new Tables\SubjectPersons;

				if (!$table->save($spData))
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
	 * @param   array  $data  the post data
	 *
	 * @return bool  true on success, otherwise false
	 */
	private function processPrerequisites($data)
	{
		$subjectID = $data['id'];

		if (!$subjectRanges = $this->getRanges($subjectID))
		{
			return true;
		}

		$programRanges = Helpers\Programs::getRanges($subjectRanges);

		$preRequisites = array_filter($data['prerequisites']);
		if (!empty($preRequisites) and array_search(self::NONE, $preRequisites) === false)
		{
			$prerequisiteRanges = [];
			foreach ($preRequisites as $preRequisiteID)
			{
				$prerequisiteRanges = array_merge($prerequisiteRanges, $this->getRanges($preRequisiteID));
			}

			$preSuccess = $this->associateDependencies($programRanges, $prerequisiteRanges, $subjectRanges, true);
		}
		else
		{
			$preSuccess = $this->removePreRequisites($subjectID);
		}

		$postSuccess    = true;
		$postRequisites = array_filter($data['postrequisites']);
		if (!empty($postRequisites) and array_search(self::NONE, $postRequisites) === false)
		{
			$postRequisiteRanges = [];
			foreach ($postRequisites as $postRequisiteID)
			{
				$postRequisiteRanges = array_merge($postRequisiteRanges, $this->getRanges($postRequisiteID));
			}

			$preSuccess = $this->associateDependencies($programRanges, $subjectRanges, $postRequisiteRanges, false);
		}
		else
		{
			$postSuccess = $this->removePostRequisites($subjectID);
		}


		return ($preSuccess and $postSuccess);
	}

	/**
	 * Creates a subject and a curricula table entries as necessary.
	 *
	 * @param   SimpleXMLElement  $XMLObject       a SimpleXML object containing rudimentary resource data
	 * @param   int               $organizationID  the id of the organization with which the resource is associated
	 * @param   int               $parentID        the  id of the parent entry in the curricula table
	 *
	 * @return bool  true on success, otherwise false
	 */
	public function processResource($XMLObject, $organizationID, $parentID)
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

			$subjects->lsfID = $lsfID;

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
	 * Removes pre- & postrequisite associations for the given subject. No access checks => this is not directly
	 * accessible and requires differing checks according to its calling context.
	 *
	 * @param   int  $subjectID  the subject id
	 *
	 * @return boolean true on success, otherwise false
	 */
	private function removeDependencies($subjectID)
	{
		if (!$this->removePreRequisites($subjectID))
		{
			return false;
		}

		return $this->removePostRequisites($subjectID);
	}

	/**
	 * Removes planSubject associations for the given subject. No access checks => this is not directly accessible and
	 * requires differing checks according to its calling context.
	 *
	 * @param   int  $subjectID  the subject id
	 *
	 * @return boolean
	 */
	/*private function removeEvents($subjectID)
	{
		$query = $this->_db->getQuery(true);
		$query->delete('#__organizer_subject_curricula')->where("subjectID = '$subjectID'");
		$this->_db->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('execute');
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
	private function removePersons($subjectID, $role = null)
	{
		$query = $this->_db->getQuery(true);
		$query->delete('#__organizer_subject_persons')->where("subjectID = $subjectID");
		if (!empty($role))
		{
			$query->where("role = $role");
		}

		$this->_db->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Removes prerequisite associations for the given subject. No access checks => this is not directly
	 * accessible and requires differing checks according to its calling context.
	 *
	 * @param   int  $subjectID  the subject id
	 *
	 * @return boolean true on success, otherwise false
	 */
	private function removePreRequisites($subjectID)
	{
		$rangeIDs      = Helpers\Subjects::filterIDs($this->getRanges($subjectID));
		$rangeIDString = implode(',', $rangeIDs);

		$query = $this->_db->getQuery(true);
		$query->delete('#__organizer_prerequisites')->where("subjectID IN ($rangeIDString)");
		$this->_db->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Removes pre- & postrequisite associations for the given subject. No access checks => this is not directly
	 * accessible and requires differing checks according to its calling context.
	 *
	 * @param   int  $subjectID  the subject id
	 *
	 * @return boolean true on success, otherwise false
	 */
	private function removePostRequisites($subjectID)
	{
		$rangeIDs      = Helpers\Subjects::filterIDs($this->getRanges($subjectID));
		$rangeIDString = implode(',', $rangeIDs);

		$query = $this->_db->getQuery(true);
		$query->delete('#__organizer_prerequisites')->where("prerequisiteID IN ($rangeIDString)");
		$this->_db->setQuery($query);

		return (bool) OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Parses the prerequisites text and replaces subject references with links to the subjects
	 *
	 * @param   string  $subjectID  the id of the subject being processed
	 *
	 * @return bool true on success, otherwise false
	 */
	private function resolveTextDependencies($subjectID)
	{
		$table = new Tables\Subjects;

		// Entry doesn't exist. Should not occur.
		if (!$table->load($subjectID))
		{
			return false;
		}

		// Subject is not associated with a program
		if (!$programRanges = Helpers\Subjects::getPrograms($subjectID))
		{
			return $this->removeDependencies($subjectID);
		}

		// Ordered by length for faster in case short is a subset of long.
		$checkedAttributes = [
			'abbreviation_de',
			'abbreviation_en',
			'code',
			'fullName_de',
			'fullName_en',
			'name_de',
			'name_en',
			'shortName_de',
			'shortName_en'
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
			$originalText  = $table->$attribute;
			$sanitizedText = Helpers\SubjectsLSF::sanitizeText($originalText);
			preg_match_all('/[\s|$]([A-Za-z0-9]{3,10})[\s|^]/', $sanitizedText, $potentialCodes);

			if (empty($potentialCodes) or empty($potentialCodes[1]))
			{
				continue;
			}

			if ($dependencies = $this->verifyDependencies($potentialCodes[1], $programRanges))
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

		if (!$this->saveDependencies($programRanges, $subjectID, $prerequisites, 'pre'))
		{
			return false;
		}

		if (!$this->saveDependencies($programRanges, $subjectID, $postrequisites, 'post'))
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
	 * @param   array  $data  the data from the form
	 *
	 * @return mixed int id of the resource on success, otherwise boolean false
	 * @throws Exception => unauthorized access
	 */
	public function save($data = [])
	{
		$data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;

		if (!$this->allow())
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_401'), 401);
		}

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

		if (!empty($data['organizationIDs']) and !$this->updateAssociations($data['id'], $data['organizationIDs']))
		{
			return false;
		}

		if (!$this->processPersons($data))
		{
			return false;
		}

		$superOrdinates = $this->getSuperOrdinates($data);

		if (!$this->addNew($data, $superOrdinates))
		{
			return false;
		}

		$this->removeDeprecated($table->id, $superOrdinates);

		// Dependant on curricula entries.
		if (!$this->processPrerequisites($data))
		{
			return false;
		}

		/*if (!$this->processEvents($data))
		{
			return false;
		}*/

		return $table->id;
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
		$subjectRanges = $this->getRanges($subjectID);

		foreach ($programs as $program)
		{
			// Program context filtered subject ranges
			$fsRanges   = $this->filterRanges($program, $subjectRanges);
			$fsRangeIDs = Helpers\Subjects::filterIDs($fsRanges);

			// Program context filtered dependency ranges
			$fdRangeIDs = [];
			foreach ($dependencies as $dependency)
			{
				$fdRanges   = $this->filterRanges($program, $dependency);
				$fdRangeIDs = array_merge($fdRangeIDs, Helpers\Subjects::filterIDs($fdRanges));
			}

			array_unique($fdRangeIDs);

			if ($type == 'pre')
			{
				$success = $this->savePrerequisites($fdRangeIDs, $fsRangeIDs);
			}
			else
			{
				$success = $this->savePrerequisites($fsRangeIDs, $fdRangeIDs);
			}

			if (!$success)
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
			OrganizerHelper::executeQuery('execute');
		}

		foreach ($prerequisiteIDs as $prerequisiteID)
		{
			foreach ($subjectIDs as $subjectID)
			{
				$table = new Tables\Prerequisites();
				if (!$table->load(['prerequisiteID' => $prerequisiteID, 'subjectID' => $subjectID]))
				{
					$table->prerequisiteID = $prerequisiteID;
					$table->subjectID      = $subjectID;

					if (!$table->store())
					{
						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Creates an association between persons, subjects and their roles for that subject.
	 *
	 * @param   int               $subjectID   the id of the subject
	 * @param   SimpleXMLElement  $dataObject  an object containing the lsf response
	 *
	 * @return bool  true on success, otherwise false
	 */
	private function setPersons($subjectID, $dataObject)
	{
		$coordinators = $dataObject->xpath('//verantwortliche');
		$persons      = $dataObject->xpath('//dozent');

		$this->removePersons($subjectID);

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
	 * @param   array  $persons    an array containing information about the subject's persons
	 * @param   int    $role       the person's role
	 *
	 * @return boolean  true on success, otherwise false
	 */
	private function setPersonsByRoles($subjectID, $persons, $role)
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

			$spData  = ['personID' => $personTable->id, 'role' => $role, 'subjectID' => $subjectID];
			$spTable = new Tables\SubjectPersons;

			if (!$spTable->save($spData))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks for subjects with the given possible module number associated with to the same programs.
	 *
	 * @param   array  $potentialCodes  the possible code values used in the attribute text
	 * @param   array  $programRanges   the program ranges whose curricula contain the subject being processed
	 *
	 * @return array the subject information for subjects with dependencies
	 */
	private function verifyDependencies($potentialCodes, $programRanges)
	{
		$select = 's.id AS subjectID, code, ';
		$select .= 'abbreviation_de, shortName_de, fullName_de, abbreviation_en, shortName_en, fullName_en, ';
		$select .= 'c.id AS curriculumID, c.lft, c.rgt, ';

		$query = $this->_db->getQuery(true);
		$query->from('#__organizer_subjects AS s')
			->innerJoin('#__organizer_curricula AS c ON c.subjectID = s.id');

		$subjects = [];
		foreach ($potentialCodes as $possibleModuleNumber)
		{
			$possibleModuleNumber = strtoupper($possibleModuleNumber);

			foreach ($programRanges as $program)
			{
				$query->clear('select')->clear('where');

				$query->select($select . "{$program['id']} AS programID")
					->where("lft > {$program['lft']} AND rgt < {$program['rgt']}")
					->where("s.code = '$possibleModuleNumber'");
				$this->_db->setQuery($query);

				if (!$curriculumSubjects = OrganizerHelper::executeQuery('loadAssocList', [], 'curriculumID'))
				{
					continue;
				}

				if (!array_key_exists($possibleModuleNumber, $subjects))
				{
					$subjects[$possibleModuleNumber] = [];
				}

				$subjects[$possibleModuleNumber] += $curriculumSubjects;
			}
		}

		return $subjects;
	}
}
