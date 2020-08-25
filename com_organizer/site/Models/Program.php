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
	 * @throws Exception unauthorized access
	 */
	public function activate()
	{
		if (!$selected = Helpers\Input::getSelectedIDs())
		{
			return false;
		}

		if (!$this->allow())
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_401'), 401);
		}

		$program = new Tables\Programs();
		foreach ($selected as $selectedID)
		{
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
	 * @throws Exception unauthorized access
	 */
	public function deactivate()
	{
		if (!$selected = Helpers\Input::getSelectedIDs())
		{
			return false;
		}

		if (!$this->allow())
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_401'), 401);
		}

		$program = new Tables\Programs();
		foreach ($selected as $selectedID)
		{
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
		$query->select('p.code AS program, d.code AS degree, p.accredited')
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

		$client = new Helpers\LSF;
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
	 * @throws Exception => unauthorized access
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
	 * @throws Exception => unauthorized access
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
				throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
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
