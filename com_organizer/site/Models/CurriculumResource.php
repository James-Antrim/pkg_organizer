<?php
/**
 * @package     Organizer\Models
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Organizer\Models;

use Organizer\Helpers;
use Organizer\Tables;

abstract class CurriculumResource extends BaseModel
{
	CONST POOL = 'K', SUBJECT = 'M';

	/**
	 * Adds a pool mapping to a parent mapping
	 *
	 * @param   array &$range  an array containing data about a curriculum item and potentially its children
	 *
	 * @return bool  true on success, otherwise false
	 */
	protected function addRange(&$range)
	{
		$parent = $range['parentID'] ? $this->getRange($range['parentID']) : null;
		$left   = $this->getLeft($range['parentID'], $range['ordering']);
		$level  = $parent ? $parent['level'] + 1 : 0;

		if (!$left or $this->shiftRight($left))
		{
			return false;
		}

		$range['level'] = $level;
		$range['lft']   = $left;
		$range['rgt']   = $left + 1;

		$curricula = new Tables\Curricula;

		if ($curricula->save($range))
		{
			if (!empty($range['curriculum']))
			{
				foreach ($range['curriculum'] as $subOrdinate)
				{
					$subOrdinate['parentID'] = $curricula->id;

					if (!$this->addRange($subOrdinate))
					{
						return false;
					}

					continue;
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * Method to delete a single range from the curricula table
	 *
	 * @param   int  $rangeID  the id value of the range to be deleted
	 *
	 * @return bool  true on success, otherwise false
	 */
	protected function deleteRange($rangeID)
	{
		if (!$range = $this->getRange($rangeID))
		{
			return false;
		}

		// Deletes the range
		$curricula = new Tables\Curricula;

		if (!$curricula->delete($rangeID))
		{
			return false;
		}

		// Reduces the ordering of siblings with a greater ordering
		if (!$this->shiftDown($range['parentID'], $range['ordering']))
		{
			return false;
		}

		$width = $range['rgt'] - $range['lft'] + 1;

		return $this->shiftLeft($range['lft'], $width);
	}

	/**
	 * Deletes ranges of a specific curriculum resource.
	 *
	 * @param   int  $resourceID  the id of the resource
	 *
	 * @return boolean true on success, otherwise false
	 */
	abstract protected function deleteRanges($resourceID);

	/**
	 * Deletes a single curriculum resource.
	 *
	 * @param   int  $resourceID  the resource id
	 *
	 * @return boolean  true on success, otherwise false
	 */
	abstract public function deleteSingle($resourceID);

	/**
	 * Gets the curriculum for a pool selected as a subordinate resource
	 *
	 * @param   int  $poolID  the resource id
	 *
	 * @return array  empty if no child data exists
	 */
	protected function getExistingCurriculum($poolID)
	{
		//Subordinate structures are the same for every parent mapping, so only the first mapping needs to be found.
		$existingQuery = $this->_db->getQuery(true);
		$existingQuery->select('id')->from('#__organizer_curricula')->where("poolID = $poolID");
		$this->_db->setQuery($existingQuery, 0, 1);

		if (!$firstID = Helpers\OrganizerHelper::executeQuery('loadResult'))
		{
			return [];
		}

		$childrenQuery = $this->_db->getQuery(true);
		$childrenQuery->select('*')->from('#__organizer_curricula')->where("parentID = $firstID")->order('lft');
		$this->_db->setQuery($childrenQuery);

		if (!$subOrdinates = Helpers\OrganizerHelper::executeQuery('loadAssocList', []))
		{
			return $subOrdinates;
		}

		foreach ($subOrdinates as $key => $subOrdinate)
		{
			if ($subOrdinate['poolID'])
			{
				$subOrdinates[$key]['curriculum'] = $this->getExistingCurriculum($subOrdinate['poolID']);
			}
		}

		return $subOrdinates;
	}

	/**
	 * Returns the resource's existing ordering in the context of its parent.
	 *
	 * @param   int  $parentID    the parent id (curricula)
	 * @param   int  $resourceID  the resource id (resource table)
	 *
	 * @return mixed int if the resource has an existing ordering, otherwise null
	 */
	abstract protected function getExistingOrdering($parentID, $resourceID);

	/**
	 * Builds the resource's curriculum using the subordinate resources contained in the form.
	 *
	 * @return array  an array containing the resource's subordinate resources
	 */
	protected function getFormCurriculum()
	{
		$index        = 1;
		$subOrdinates = [];
		while (Helpers\Input::getInt("child{$index}Order"))
		{
			$ordering      = Helpers\Input::getInt("child{$index}Order");
			$aggregateInfo = Helpers\Input::getCMD("child{$index}");

			if (!empty($aggregateInfo))
			{
				$resourceID   = substr($aggregateInfo, 0, strlen($aggregateInfo) - 1);
				$resourceType = strpos($aggregateInfo, 'p') ? 'pool' : 'subject';

				if ($resourceType == 'subject')
				{
					$subOrdinates[$ordering]['poolID']    = null;
					$subOrdinates[$ordering]['subjectID'] = $resourceID;
					$subOrdinates[$ordering]['ordering']  = $ordering;
				}

				if ($resourceType == 'pool')
				{
					$subOrdinates[$ordering]['poolID']     = $resourceID;
					$subOrdinates[$ordering]['subjectID']  = null;
					$subOrdinates[$ordering]['ordering']   = $ordering;
					$subOrdinates[$ordering]['curriculum'] = $this->getExistingCurriculum($resourceID);
				}
			}

			$index++;
		}

		return $subOrdinates;
	}

	/**
	 * Attempt to determine the left value for the range to be created
	 *
	 * @param   int    $parentID  the parent of the item to be inserted
	 * @param   mixed  $ordering  the targeted ordering on completion
	 *
	 * @return int  int the left value for the range to be created, or 0 on error
	 */
	protected function getLeft($parentID, $ordering)
	{
		if (!$parentID)
		{
			$query = $this->_db->getQuery(true);
			$query->select('MAX(rgt) + 1')->from('#__organizer_curricula');
			$this->_db->setQuery($query);

			$left = Helpers\OrganizerHelper::executeQuery('loadResult');

			return $left ? $left : false;
		}

		// Right value of the next lowest sibling
		$rgtQuery = $this->_db->getQuery(true);
		$rgtQuery->select('MAX(rgt)')
			->from('#__organizer_curricula')
			->where("parentID = $parentID")
			->where("ordering < $ordering");
		$this->_db->setQuery($rgtQuery);

		if (!$rgt = Helpers\OrganizerHelper::executeQuery('loadResult'))
		{
			return $rgt + 1;
		}

		// No siblings => use parent left for reference
		$lftQuery = $this->_db->getQuery(true);
		$lftQuery->select('lft')
			->from('#__organizer_mappings')
			->where("id = $parentID");
		$this->_db->setQuery($lftQuery);
		$lft = Helpers\OrganizerHelper::executeQuery('loadResult');

		return empty($lft) ? 0 : $lft + 1;
	}

	/**
	 * Retrieves the existing ordering of a pool to its parent item, or next highest value in the series
	 *
	 * @param   int  $parentID    the id of the parent mapping
	 * @param   int  $resourceID  the id of the resource
	 *
	 * @return int  the value of the highest existing ordering or 1 if none exist
	 */
	protected function getOrdering($parentID, $resourceID)
	{
		if ($existingOrdering = $this->getExistingOrdering($parentID, $resourceID))
		{
			return $existingOrdering;
		}

		$query = $this->_db->getQuery(true);
		$query->select('MAX(ordering)')->from('#__organizer_curricula')->where("parentID = $parentID");
		$this->_db->setQuery($query);

		if ($maxOrder = Helpers\OrganizerHelper::executeQuery('loadResult'))
		{
			return $maxOrder + 1;
		}

		return 1;
	}

	/**
	 * Retrieves the range for a given id.
	 *
	 * @param   int  $rangeID  the id of the range requested
	 *
	 * @return array  curriculum range
	 */
	protected function getRange($rangeID)
	{
		$parentQuery = $this->_db->getQuery(true);
		$parentQuery->select('*')->from('#__organizer_curricula')->where("id = $rangeID");
		$this->_db->setQuery($parentQuery);

		return Helpers\OrganizerHelper::executeQuery('loadAssoc', []);
	}

	/**
	 * Imports a program from the LSF Module
	 *
	 * @param   int     $resourceID  the id of the curriculum resource
	 * @param   object &$XMLObject   the data received from the LSF system
	 *
	 * @return boolean  true if the data was mapped, otherwise false
	 */
	abstract public function import($resourceID, &$XMLObject);

	/**
	 * Saves the resource's curriculum information.
	 *
	 * @param   array  $data  the data from the form
	 *
	 * @return bool true on success, otherwise false
	 */
	abstract protected function saveCurriculum($data);

	/**
	 * Shifts the ordering for existing siblings who have an ordering at or above the ordering to be inserted.
	 *
	 * @param   int  $parentID  the id of the parent
	 * @param   int  $ordering  the ordering of the item to be inserted
	 *
	 * @return boolean  true on success, otherwise false
	 */
	protected function shiftDown($parentID, $ordering)
	{
		$query = $this->_db->getQuery(true);
		$query->update('#__organizer_curricula')
			->set('ordering = ordering - 1')
			->where("ordering > $ordering")
			->where("parentID = $parentID");
		$this->_db->setQuery($query);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Shifts left and right values to allow for the values to be inserted
	 *
	 * @param   int  $left   the integer value above which left and right values need to be shifted
	 * @param   int  $width  the width of the item being deleted
	 *
	 * @return bool  true on success, otherwise false
	 */
	protected function shiftLeft($left, $width)
	{
		$lftQuery = $this->_db->getQuery(true);
		$lftQuery->update('#__organizer_curricula')->set("lft = lft - $width")->where("lft > $left");
		$this->_db->setQuery($lftQuery);

		if (!Helpers\OrganizerHelper::executeQuery('execute'))
		{
			return false;
		}

		$rgtQuery = $this->_db->getQuery(true);
		$rgtQuery->update('#__organizer_mappings')->set("rgt = rgt - $width")->where("rgt > $left");
		$this->_db->setQuery($rgtQuery);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Shifts left and right values to allow for the values to be inserted
	 *
	 * @param   int  $left   the integer value above which left and right values
	 *                       need to be shifted
	 *
	 * @return bool  true on success, otherwise false
	 */
	protected function shiftRight($left)
	{
		$lftQuery = $this->_db->getQuery(true);
		$lftQuery->update('#__organizer_curricula')->set('lft = lft + 2')->where("lft >= '$left'");
		$this->_db->setQuery($lftQuery);

		if (!Helpers\OrganizerHelper::executeQuery('execute'))
		{
			return false;
		}

		$rgtQuery = $this->_db->getQuery(true);
		$rgtQuery->update('#__organizer_mappings')->set('rgt = rgt + 2')->where("rgt >= '$left'");
		$this->_db->setQuery($rgtQuery);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute');
	}

	/**
	 * Shifts the ordering for existing siblings who have an ordering at or above the ordering to be inserted.
	 *
	 * @param   int  $parentID  the id of the parent
	 * @param   int  $ordering  the ordering of the item to be inserted
	 *
	 * @return boolean  true on success, otherwise false
	 */
	protected function shiftUp($parentID, $ordering)
	{
		$query = $this->_db->getQuery(true);
		$query->update('#__organizer_curricula')
			->set('ordering = ordering + 1')
			->where("ordering >= $ordering")
			->where("parentID = $parentID");
		$this->_db->setQuery($query);

		return (bool) Helpers\OrganizerHelper::executeQuery('execute');
	}
}