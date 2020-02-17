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
use Organizer\Tables;

/**
 * Class which manages stored (degree) program data.
 */
class Program extends CurriculumResource
{
	protected $resource = 'program';

	/**
	 * Deletes ranges of a specific curriculum resource.
	 *
	 * @param   int  $resourceID  the id of the mapping
	 *
	 * @return boolean true on success, otherwise false
	 */
	protected function deleteRanges($resourceID)
	{
		if ($rangeIDs = Helpers\Programs::getRangeIDs($resourceID))
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

		$table = new Tables\Programs;

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
		return null;
	}

	/**
	 * Retrieves program information relevant for soap queries to the LSF system.
	 *
	 * @param   int  $programID  the id of the degree program
	 *
	 * @return array  empty if the program could not be found
	 */
	private function getKeys($programID)
	{
		$query = $this->_db->getQuery(true);
		$query->select('p.code AS program, d.code AS degree, accredited, organizationID')
			->from('#__organizer_programs AS p')
			->leftJoin('#__organizer_degrees AS d ON d.id = p.degreeID')
			->where("p.id = '$programID'");
		$this->_db->setQuery($query);

		return Helpers\OrganizerHelper::executeQuery('loadAssoc', []);
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Programs A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Programs;
	}

	/**
	 * Method to import data associated with resources from LSF
	 *
	 * @return bool true on success, otherwise false
	 */
	public function import()
	{
		$programIDs = Helpers\Input::getSelectedIDs();

		foreach ($programIDs as $programID)
		{
			if (!$this->importSingle($programID))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Method to import data associated with a resource from LSF
	 *
	 * @param   int  $resourceID  the id of the program to be imported
	 *
	 * @return boolean  true on success, otherwise false
	 */
	public function importSingle($resourceID)
	{
		if (!$keys = $this->getKeys($resourceID))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_LSF_DATA_MISSING', 'error');

			return false;
		}

		$client = new Helpers\LSF;
		if (!$program = $client->getModules($keys['program'], $keys['degree'], $keys['accredited']))
		{
			return false;
		}

		// The program has not been completed in LSF.
		if (empty($program->gruppe))
		{
			return true;
		}

		$curriculumID = 0;

		// Curriculum entry doesn't exist and could not be created.
		if (!Helpers\Programs::getRanges($resourceID) and !$curriculumID = $this->saveCurriculum($resourceID))
		{
			return false;
		}

		return $this->processCollection($program->gruppe, $keys['organizationID'], $curriculumID);
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
		// There is no legitimate call to this method.
		return false;
	}

	/**
	 * Attempts to save the resource.
	 *
	 * @param   array  $data  form data
	 *
	 * @return mixed int id of the resource on success, otherwise boolean false
	 * @throws Exception => invalid request, unauthorized access
	 */
	public function save($data = [])
	{
		$data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;

		if (empty($data['id']))
		{
			// New program can be saved explicitly by documenters or implicitly by schedulers.
			$documentationAccess = (bool) Helpers\Can::documentTheseOrganizations();
			$schedulingAccess    = (bool) Helpers\Can::scheduleTheseOrganizations();

			if (!($documentationAccess or $schedulingAccess))
			{
				throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
			}
		}
		elseif (is_numeric($data['id']))
		{
			if (!Helpers\Can::document('program', $data['id']))
			{
				throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
			}
		}
		else
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_400'), 400);
		}

		$table = new Tables\Programs;

		if ($table->save($data))
		{
			return false;
		}

		return $this->saveCurriculum($table->id) ? $table->id : false;
	}

	/**
	 * Method to save existing degree programs as copies
	 *
	 * @param   array  $data  the data to be used to create the program when called from the program helper
	 *
	 * @return Boolean
	 * @throws Exception => unauthorized access
	 */
	public function save2copy($data = [])
	{
		if (!Helpers\Can::documentTheseOrganizations())
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
		}

		$data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;
		if (isset($data['id']))
		{
			unset($data['id']);
		}

		$table = new Tables\ProgramsTable;

		if (!$table->save($data))
		{
			return false;
		}

		return $this->saveCurriculum($table->id) ? $table->id : false;
	}

	/**
	 * Saves the resource's curriculum information.
	 *
	 * @param   int  $programID  the programID
	 *
	 * @return bool true on success, otherwise false
	 */
	public function saveCurriculum($programID)
	{
		$range = ['parentID' => null, 'programID' => $programID, 'curriculum' => $this->getFormCurriculum()];

		// The curriculum has been modelled in the range => purge.
		if (!$this->deleteRanges($range['programID']))
		{
			return 0;
		}

		return $this->addRange($range);
	}

	/**
	 * Method to update subject data associated with degree programs from LSF
	 *
	 * @return bool  true on success, otherwise false
	 * @throws Exception => unauthorized access
	 */
	public function update()
	{
		$programIDs = Helpers\Input::getSelectedIDs();

		if (empty($programIDs))
		{
			return false;
		}

		$subject = new Subject;

		foreach ($programIDs as $programID)
		{
			if (!Helpers\Can::document('program', $programID))
			{
				throw new Exception(Languages::_('ORGANIZER_403'), 403);
			}

			if (!$subjectIDs = Helpers\Programs::getSubjectIDs($programID))
			{
				continue;
			}

			foreach ($subjectIDs as $subjectID)
			{
				if (!$subject->importSingle($subjectID))
				{
					return false;
				}
			}
		}

		return true;
	}
}
