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

use Organizer\Adapters\Database;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored (degree) program data.
 */
class Program extends CurriculumResource
{
	use Associated;
	use SuperOrdinate;

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
	private function getKeys(int $programID)
	{
		$query = Database::getQuery();
		$query->select('p.code AS program, d.code AS degree, p.accredited, a.organizationID')
			->from('#__organizer_programs AS p')
			->leftJoin('#__organizer_degrees AS d ON d.id = p.degreeID')
			->innerJoin('#__organizer_associations AS a ON a.programID = p.id')
			->where("p.id = $programID");
		Database::setQuery($query);

		return Database::loadAssoc();
	}

	/**
	 * Finds the curriculum entry ids for subject entries subordinate to a particular resource.
	 *
	 * @param   int  $resourceID  the id of the resource
	 * @param   int  $subjectID   the id of a specific subject resource to find in context
	 *
	 * @return array the associated programs
	 */
	private function getSubjectIDs(int $resourceID, $subjectID = 0)
	{
		$ranges = Helpers\Programs::getSubjects($resourceID, $subjectID);

		$ids = [];
		foreach ($ranges as $range)
		{
			if ($range['subjectID'])
			{
				$ids[] = $range['subjectID'];
			}
		}

		return $ids;
	}

	/**
	 * @inheritDoc
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Programs();
	}

	/**
	 * @inheritDoc
	 */
	public function importSingle(int $resourceID)
	{
		if (!$keys = $this->getKeys($resourceID))
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

		if (!$ranges = $this->getRanges($resourceID) or empty($ranges[0]))
		{
			$range = ['parentID' => null, 'programID' => $resourceID, 'ordering' => 0];

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
	 * @inheritDoc
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

		$range = ['parentID' => null, 'programID' => $table->id, 'curriculum' => $this->getSubOrdinates(), 'ordering' => 0];

		if (!$this->addRange($range))
		{
			return false;
		}

		return $table->id;
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

			if (!$subjectIDs = $this->getSubjectIDs($programID))
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
