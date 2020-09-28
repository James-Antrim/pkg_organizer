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

use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored (degree) program data.
 */
class Program extends CurriculumResource
{
	use Associated, SuperOrdinate;

	protected $helper = 'Programs';

	protected $resource = 'program';

	/**
	 * Activates programs by id.
	 *
	 * @return bool true on success, otherwise false
	 */
	public function activate()
	{
		if (!$selected = Helpers\Input::getSelectedIDs())
		{
			return false;
		}

		$this->authorize();

		foreach ($selected as $selectedID)
		{
			$program = new Tables\Programs();

			if ($program->load($selectedID))
			{
				$program->active = 1;
				$program->store();
				continue;
			}

			return false;
		}

		return true;
	}

	/**
	 * Deactivates programs by id.
	 *
	 * @return bool true on success, otherwise false
	 */
	public function deactivate()
	{
		if (!$selected = Helpers\Input::getSelectedIDs())
		{
			return false;
		}

		$this->authorize();

		foreach ($selected as $selectedID)
		{
			$program = new Tables\Programs();

			if ($program->load($selectedID))
			{
				$program->active = 0;
				$program->store();
				continue;
			}

			return false;
		}

		return true;

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
		$query->select('p.code AS program, d.code AS degree, p.accredited, a.organizationID')
			->from('#__organizer_programs AS p')
			->leftJoin('#__organizer_degrees AS d ON d.id = p.degreeID')
			->innerJoin('#__organizer_associations AS a ON a.programID = p.id')
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
		return new Tables\Programs();
	}

	/**
	 * Method to import data associated with a resource from LSF
	 *
	 * @param   int  $programID  the id of the program to be imported
	 *
	 * @return boolean  true on success, otherwise false
	 */
	public function importSingle($programID)
	{
		if (!$keys = $this->getKeys($programID))
		{
			Helpers\OrganizerHelper::message('ORGANIZER_LSF_DATA_MISSING', 'error');

			return false;
		}

		$client = new Helpers\LSF();
		if (!$program = $client->getModules($keys))
		{
			return false;
		}

		// The program has not been completed in LSF.
		if (empty($program->gruppe))
		{
			return true;
		}

		if (!$ranges = $this->getRanges($programID) or empty($ranges[0]))
		{
			$range = ['parentID' => null, 'programID' => $programID];

			return $this->addRange($range);
		}
		else
		{
			$curriculumID = $ranges[0]['id'];
		}

		// Curriculum entry doesn't exist and could not be created.
		if (empty($curriculumID))
		{
			return false;
		}

		return $this->processCollection($program->gruppe, $keys['organizationID'], $curriculumID);
	}

	/**
	 * Attempts to save the resource.
	 *
	 * @param   array  $data  form data
	 *
	 * @return int|bool the id of the resource on success, otherwise boolean false
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
				Helpers\OrganizerHelper::error(403);
			}
		}
		elseif (is_numeric($data['id']))
		{
			if (!Helpers\Can::document('program', (int) $data['id']))
			{
				Helpers\OrganizerHelper::error(403);
			}
		}
		else
		{
			return false;
		}

		$table = new Tables\Programs();

		if (!$table->save($data))
		{
			return false;
		}

		$data['id'] = $table->id;

		if (!empty($data['organizationIDs']) and !$this->updateAssociations($data['id'], $data['organizationIDs']))
		{
			return false;
		}

		$range = ['parentID' => null, 'programID' => $table->id, 'curriculum' => $this->getSubOrdinates()];

		if (!$this->addRange($range))
		{
			return false;
		}

		return $table->id;
	}

	/**
	 * Method to save existing degree programs as copies.
	 *
	 * @param   array  $data  the data to be used to create the program when called from the program helper
	 *
	 * @return int|bool the id of the resource on success, otherwise boolean false
	 */
	public function save2copy($data = [])
	{
		$data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;
		unset($data['id']);

		return $this->save($data);
	}

	/**
	 * Method to update subject data associated with degree programs from LSF
	 *
	 * @return bool  true on success, otherwise false
	 */
	public function update()
	{
		$programIDs = Helpers\Input::getSelectedIDs();

		if (empty($programIDs))
		{
			return false;
		}

		$subject = new Subject();

		foreach ($programIDs as $programID)
		{
			if (!Helpers\Can::document('program', $programID))
			{
				Helpers\OrganizerHelper::error(403);
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
