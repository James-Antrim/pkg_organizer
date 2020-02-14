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
 * Class which manages stored (subject) pool data.
 */
class Pool extends CurriculumResource
{
	/**
	 * Attempts to delete the selected subject pool entries and related mappings
	 *
	 * @return boolean true on success, otherwise false
	 * @throws Exception => unauthorized access
	 */
	public function delete()
	{
		if (!Helpers\Can::documentTheseOrganizations())
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
		}

		if ($poolIDs = Helpers\Input::getSelectedIDs())
		{
			foreach ($poolIDs as $poolID)
			{
				if (!Helpers\Can::document('pool', $poolID))
				{
					throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
				}

				if (!$this->deleteSingle($poolID))
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
		if ($rangeIDs = Helpers\Pools::getRangeIDs($resourceID))
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

		$table = new Tables\Pools;

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
	protected function getExistingOrdering($parentID, $resourceID)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('ordering')
			->from('#__organizer_curricula')
			->where("parentID = '$parentID'")
			->where("poolID = '$resourceID'");
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
		return new Tables\Pools;
	}

	/**
	 * Adds a pool from LSF to the mappings table.
	 *
	 * @param   int     $parentID   the id of the parent mapping
	 * @param   object &$XMLObject  the object representing the LSF pool
	 *
	 * @return boolean  true if the pool is mapped, otherwise false
	 * @see PoolLSF processStub()
	 */
	public function import($parentID, &$XMLObject)
	{
		$blocked = !empty($XMLObject->sperrmh) and strtolower((string) $XMLObject->sperrmh) === 'x';
		$lsfID        = empty($XMLObject->pordid) ? (string) $XMLObject->modulid : (string) $XMLObject->pordid;
		$invalidTitle = Helpers\LSF::invalidTitle($XMLObject);
		$noChildren   = !isset($XMLObject->modulliste->modul);
		$pools        = new Tables\Pools;

		if (!$pools->load(['lsfID' => $lsfID]))
		{
			if ($blocked or $invalidTitle or $noChildren)
			{
				return true;
			}

			Helpers\OrganizerHelper::message('ORGANIZER_POOL_IMPORT_FAIL', 'error');

			return false;
		}

		// Unwanted, invalid or irrelevant
		if ($blocked or $invalidTitle or $noChildren)
		{
			return $this->deleteSingle($pools->id);
		}

		$curricula = new Tables\Curricula;

		if (!$curricula->load(['parentID' => $parentID, 'poolID' => $pools->id]))
		{
			$range             = [];
			$range['parentID'] = $parentID;
			$range['poolID']   = $pools->id;

			$range['ordering'] = $this->getOrdering($parentID, $pools->id);
			if (!$this->shiftUp($parentID, $range['ordering']))
			{
				return false;
			}

			if (!$this->addRange($range))
			{
				Helpers\OrganizerHelper::message('ORGANIZER_POOL_ADD_FAIL', 'error');

				return false;
			}

			$curricula->load(['parentID' => $parentID, 'poolID' => $pools->id]);
		}

		foreach ($XMLObject->modulliste->modul as $subOrdinate)
		{
			$type = (string) $subOrdinate->pordtyp;

			if ($type === self::POOL)
			{
				if (!$this->import($curricula->id, $subOrdinate))
				{
					return false;
				}

				continue;
			}

			if ($type === self::SUBJECT)
			{
				$subject = new Subject;
				if (!$subject->import($curricula->id, $subOrdinate))
				{
					return false;
				}

				continue;
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
			if (!Helpers\Can::documentTheseOrganizations())
			{
				throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
			}
		}
		elseif (is_numeric($data['id']))
		{
			if (!Helpers\Can::document('pool', $data['id']))
			{
				throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
			}
		}
		else
		{
			throw new Exception(Helpers\Languages::_('ORGANIZER_400'), 400);
		}

		$table = new Tables\Pools;

		if (!$table->save($data))
		{
			return false;
		}

		$data['id'] = $table->id;

		// If no the form has associations the purge has to take place after the curriculum is modeled.
		if (empty($data['parentID']))
		{
			return $this->deleteRanges($table->id) ? $table->id : false;
		}

		return $this->saveCurriculum($data) ? $table->id : false;
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
		$range = ['poolID' => $data['id'], 'curriculum' => $this->getFormCurriculum()];

		// The curriculum has been modelled in the range => purge.
		if (!$this->deleteRanges($range['poolID']))
		{
			return false;
		}

		foreach ($data['parentID'] as $parentID)
		{
			$range['parentID'] = $parentID;
			$range['ordering'] = $this->getOrdering($parentID, $range['poolID']);

			if (!$this->addRange($range))
			{
				Helpers\OrganizerHelper::message('ORGANIZER_POOL_ADD_FAIL', 'error');

				return false;
			}
		}

		return true;
	}
}
