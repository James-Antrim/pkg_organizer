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
use Joomla\CMS\Table\Table;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class which manages stored (degree) program data.
 */
class Program extends CurriculumResource
{
	/**
	 * Attempts to delete the selected degree program entries and related mappings
	 *
	 * @return boolean  True if successful, false if an error occurs.
	 * @throws Exception => unauthorized access
	 */
	public function delete()
	{
		if (!Helpers\Can::documentTheseOrganizations())
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
		}

		if ($programIDs = Helpers\Input::getSelectedIDs())
		{
			foreach ($programIDs as $programID)
			{
				if (!Helpers\Can::document('program', $programID))
				{
					throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
				}

				if (!$this->deleteSingle($programID))
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
		return new Tables\ProgramsTable;
	}

	/**
	 * Imports a program
	 *
	 * @param   int     $resourceID  the id of the curriculum resource
	 * @param   object &$XMLObject   the data received from the LSF system
	 *
	 * @return boolean  true if the data was mapped, otherwise false
	 */
	public function import($resourceID, &$XMLObject)
	{
		$curricula = new Tables\Curricula;

		if (!$curricula->load(['programID' => $resourceID]))
		{
			return false;
		}

		foreach ($XMLObject->gruppe as $subOrdinate)
		{
			$type = (string) $subOrdinate->pordtyp;

			if ($type === self::POOL)
			{
				$pool = new Pool;
				if ($pool->import($curricula->id, $subOrdinate))
				{
					continue;
				}

				return false;
			}

			if ($type === self::SUBJECT)
			{
				$subject = new Subject;
				if ($subject->import($curricula->id, $subOrdinate))
				{
					continue;
				}

				return false;
			}
		}

		return true;
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
			return false;
		}

		return $this->addRange($range) ? true : false;
	}
}
