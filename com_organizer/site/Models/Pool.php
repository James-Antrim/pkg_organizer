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
 * Class which manages stored (subject) pool data.
 */
class Pool extends CurriculumResource
{
	protected $resource = 'pool';

	/**
	 * Deletes ranges of a specific curriculum resource.
	 *
	 * @param   int  $resourceID  the id of the resource in its specific resource table
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
		$query = $this->_db->getQuery(true);
		$query->select('ordering')
			->from('#__organizer_curricula')
			->where("parentID = '$parentID'")
			->where("poolID = '$resourceID'");
		$this->_db->setQuery($query);

		return Helpers\OrganizerHelper::executeQuery('loadResult', null);
	}

	/**
	 * Gets the mapped curricula ranges for the given resource
	 *
	 * @param   int  $poolID  the resource id
	 *
	 * @return array the resource ranges
	 */
	protected function getRanges($poolID)
	{
		return Helpers\Pools::getRanges($poolID);
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Pools A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Pools;
	}

	/**
	 * Method to import data associated with resources from LSF
	 *
	 * @return bool true on success, otherwise false
	 */
	public function import()
	{
		// There is no legitimate call to this method.
		return false;
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
		// There is no legitimate call to this method.
		return false;
	}

	/**
	 * Saves the resource's curriculum information.
	 *
	 * @param   array  $data  the data from the form
	 *
	 * @return bool true on success, otherwise false
	 */
	protected function processCurricula($data)
	{
		$pRanges             = $this->getRanges($data['id']);
		$superOrdinateRanges = $this->getSuperOrdinateRanges($data, 'pool');

		foreach ($superOrdinateRanges as $sorIndex => $superOrdinateRange)
		{
			foreach ($pRanges as $pIndex => $pRange)
			{
				// There is an existing relationship
				if ($pRange['lft'] > $superOrdinateRange['lft'] and $pRange['rgt'] < $superOrdinateRange['rgt'])
				{
					// Prevent further iteration of an established relationship
					unset($pRanges[$pIndex]);
					continue 2;
				}
			}

			$range = [
				'poolID'   => $data['id'],
				'parentID' => $superOrdinateRange['id'],
				'ordering' => $this->getOrdering($superOrdinateRange['id'], $data['id'])
			];

			if (!$this->addRange($range))
			{
				return false;
			}
		}

		$pRanges = $this->getRanges($data['id']);

		foreach ($pRanges as $pIndex => $pRange)
		{
			foreach ($superOrdinateRanges as $sorIndex => $superOrdinateRange)
			{
				// The range boundaries will have changed after an add => reload
				$superOrdinateRange = Helpers\Curricula::getRange($superOrdinateRange['id']);

				// Relationship requested and established
				if ($pRange['lft'] > $superOrdinateRange['lft'] and $pRange['rgt'] < $superOrdinateRange['rgt'])
				{
					// Prevent further iteration of an established relationship
					unset($superOrdinateRanges[$sorIndex]);
					continue 2;
				}
			}

			// Remove unrequested existing relationship
			$this->deleteRange($pRange['id']);
		}

		return true;
	}

	/**
	 * Creates a pool entry if none exists and calls
	 *
	 * @param   object &$XMLObject       a SimpleXML object containing rudimentary subject data
	 * @param   int     $organizationID  the id of the organization to which this data belongs
	 *
	 * @return bool  true on success, otherwise false
	 */
	public function processResource(&$XMLObject, $organizationID, $parentID)
	{
		$lsfID = empty($XMLObject->pordid) ? (string) $XMLObject->modulid : (string) $XMLObject->pordid;
		if (empty($lsfID))
		{
			return false;
		}

		$blocked = !empty($XMLObject->sperrmh) and strtolower((string) $XMLObject->sperrmh) === 'x';
		$noChildren = !isset($XMLObject->modulliste->modul);
		$validTitle = $this->validTitle($XMLObject);

		$pools = new Tables\Pools;

		if (!$pools->load(['lsfID' => $lsfID]))
		{
			// There isn't one and shouldn't be one
			if ($blocked or !$validTitle or $noChildren)
			{
				return true;
			}

			$pools->organizationID = $organizationID;
			$pools->lsfID          = $lsfID;
			$this->setNameAttributes($pools, $XMLObject);

			if (!$pools->store())
			{
				return false;
			}
		}
		elseif ($blocked or !$validTitle or $noChildren)
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
				return false;
			}

			$curricula->load(['parentID' => $parentID, 'poolID' => $pools->id]);
		}

		return $this->processCollection($XMLObject->modulliste->modul, $organizationID, $curricula->id);
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

		if (!empty($data['organizationIDs']) and !$this->updateAssociations($data['id'], $data['organizationIDs']))
		{
			return false;
		}

		if (!$this->processCurricula($data))
		{
			return false;
		}

		return $table->id;
	}
}
