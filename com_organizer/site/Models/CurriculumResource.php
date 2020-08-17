<?php
/**
 * @package     Organizer\Models
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Organizer\Models;

use Exception;
use Organizer\Helpers;
use Organizer\Helpers\OrganizerHelper;
use Organizer\Tables;
use SimpleXMLElement;

abstract class CurriculumResource extends BaseModel
{
	use Associated;

	const NONE = -1, POOL = 'K', SUBJECT = 'M';

	protected $helper;

	protected $resource;

	/**
	 * Adds a curriculum range to a parent curriculum range
	 *
	 * @param   array &$range  an array containing data about a curriculum item and potentially its children
	 *
	 * @return int the id of the curriculum row on success, otherwise 0
	 */
	protected function addRange(&$range)
	{
		$curricula = new Tables\Curricula;

		if (empty($range['programID']))
		{
			// Subordinates must have a parent
			if (empty($range['parentID']) or !$parent = Helpers\Curricula::getRange($range['parentID']))
			{
				return 0;
			}

			// No resource
			if (empty($range['poolID']) and empty($range['subjectID']))
			{
				return 0;
			}

			$conditions = ['parentID' => $range['parentID']];

			if (empty($range['subjectID']))
			{
				$conditions['poolID'] = $range['poolID'];
			}
			else
			{
				$conditions['subjectID'] = $range['subjectID'];
			}
		}
		else
		{
			$conditions = ['programID' => $range['programID']];
			$parent     = null;
		}


		if ($curricula->load($conditions))
		{
			$curricula->ordering = $range['ordering'];
			if (!$curricula->store())
			{
				return 0;
			}
		}
		else
		{
			$range['lft'] = $this->getLeft($range['parentID'], $range['ordering']);

			if (!$range['lft'] or !$this->shiftRight($range['lft']))
			{
				return 0;
			}

			$range['level'] = $parent ? $parent['level'] + 1 : 0;
			$range['rgt']   = $range['lft'] + 1;

			if (!$curricula->save($range))
			{
				return 0;
			}
		}

		if (!empty($range['curriculum']))
		{
			foreach ($range['curriculum'] as $subOrdinate)
			{
				$subOrdinate['parentID'] = $curricula->id;

				if (!$this->addRange($subOrdinate))
				{
					return 0;
				}

				continue;
			}
		}

		return $curricula->id;
	}

	/**
	 * Authorizes the user
	 */
	protected function allow()
	{
		if (!$id = Helpers\Input::getID())
		{
			if (Helpers\Can::documentTheseOrganizations())
			{
				return true;
			}
		}

		return Helpers\Can::document($this->resource, $id);
	}

	/**
	 * Attempts to delete the selected resources and their associations
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

		if ($resourceIDs = Helpers\Input::getSelectedIDs())
		{
			foreach ($resourceIDs as $resourceID)
			{
				if (!Helpers\Can::document($this->resource, $resourceID))
				{
					throw new Exception(Helpers\Languages::_('ORGANIZER_403'), 403);
				}

				if (!$this->deleteSingle($resourceID))
				{
					return false;
				}
			}
		}

		return true;
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
		if (!$range = Helpers\Curricula::getRange($rangeID))
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
	protected function deleteRanges($resourceID)
	{
		$helper = "Organizer\\Helpers\\" . $this->helper;
		if ($rangeIDs = $helper::getRangeIDs($resourceID))
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
	protected function deleteSingle($resourceID)
	{
		if (!$this->deleteRanges($resourceID))
		{
			return false;
		}

		$table = $this->getTable();

		return $table->delete($resourceID);
	}

	/**
	 * Gets the curriculum for a pool selected as a subordinate resource
	 *
	 * @param   int  $poolID  the resource id
	 *
	 * @return array  empty if no child data exists
	 */
	protected function getExistingCurriculum($poolID)
	{
		// Subordinate structures are the same for every superordinate resource
		$existingQuery = $this->_db->getQuery(true);
		$existingQuery->select('id')->from('#__organizer_curricula')->where("poolID = $poolID");
		$this->_db->setQuery($existingQuery, 0, 1);

		if (!$firstID = OrganizerHelper::executeQuery('loadResult', 0))
		{
			return [];
		}

		$childrenQuery = $this->_db->getQuery(true);
		$childrenQuery->select('*')->from('#__organizer_curricula')->where("parentID = $firstID")->order('lft');
		$this->_db->setQuery($childrenQuery);

		if (!$subOrdinates = OrganizerHelper::executeQuery('loadAssocList', []))
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
	protected function getExistingOrdering($parentID, $resourceID)
	{
		$column = $this->resource . 'ID';
		$query  = $this->_db->getQuery(true);
		$query->select('ordering')
			->from('#__organizer_curricula')
			->where("parentID = $parentID")
			->where("$column = $resourceID");
		$this->_db->setQuery($query);

		return OrganizerHelper::executeQuery('loadResult', 0);
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

			$left = OrganizerHelper::executeQuery('loadResult', 0);

			return $left ? $left : false;
		}

		// Right value of the next lowest sibling
		$rgtQuery = $this->_db->getQuery(true);
		$rgtQuery->select('MAX(rgt)')
			->from('#__organizer_curricula')
			->where("parentID = $parentID")
			->where("ordering < $ordering");
		$this->_db->setQuery($rgtQuery);

		if ($rgt = OrganizerHelper::executeQuery('loadResult', 0))
		{
			return $rgt + 1;
		}

		// No siblings => use parent left for reference
		$lftQuery = $this->_db->getQuery(true);
		$lftQuery->select('lft')
			->from('#__organizer_curricula')
			->where("id = $parentID");
		$this->_db->setQuery($lftQuery);
		$lft = OrganizerHelper::executeQuery('loadResult', 0);

		return empty($lft) ? 0 : $lft + 1;
	}

	/**
	 * Retrieves the existing ordering of a pool to its parent item, or next highest value in the series
	 *
	 * @param   int  $parentID    the id of the parent range
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

		$maxOrder = OrganizerHelper::executeQuery('loadResult', 0);

		return $maxOrder + 1;
	}

	/**
	 * Gets the mapped curricula ranges for the given resource
	 *
	 * @param   int  $resourceID  the resource id
	 *
	 * @return array the resource ranges
	 */
	protected function getRanges($resourceID)
	{
		$helper = "Organizer\\Helpers\\" . $this->helper;

		return $helper::getRanges($resourceID);
	}

	/**
	 * Method to import data associated with resources from LSF
	 *
	 * @return bool true on success, otherwise false
	 */
	public function import()
	{
		foreach (Helpers\Input::getSelectedIDs() as $resourceID)
		{
			if (!$this->importSingle($resourceID))
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
	abstract public function importSingle($resourceID);

	/**
	 * Iterates a collection of resources subordinate to the calling resource. Creating structure and data elements as
	 * needed.
	 *
	 * @param   SimpleXMLElement  $collection      the SimpleXML node containing the collection of subordinate elements
	 * @param   int               $organizationID  the id of the organization with which the resources are associated
	 * @param   int               $parentID        the id of the curriculum entry for the parent element.
	 *
	 * @return bool true on success, otherwise false
	 */
	protected function processCollection($collection, $organizationID, $parentID)
	{
		$pool    = new Pool;
		$subject = new Subject;

		foreach ($collection as $subOrdinate)
		{
			$type = (string) $subOrdinate->pordtyp;

			if ($type === self::POOL)
			{
				if ($pool->processResource($subOrdinate, $organizationID, $parentID))
				{
					continue;
				}

				return false;
			}

			if ($type === self::SUBJECT)
			{
				if ($subject->processResource($subOrdinate, $organizationID, $parentID))
				{
					continue;
				}

				return false;
			}
		}

		return true;
	}

	/**
	 * Sets the value of a generic attribute if available
	 *
	 * @param   Tables\BaseTable  $table    the array where subject data is being stored
	 * @param   string            $column   the key where the value should be put
	 * @param   string            $value    the value string
	 * @param   string            $default  the default value
	 *
	 * @return void
	 */
	protected function setAttribute($table, $column, $value, $default = '')
	{
		$table->$column = empty($value) ? $default : $value;
	}

	/**
	 * Set name attributes common to pools and subjects
	 *
	 * @param   Tables\Pools|Tables\Subjects  $table      the table to modify
	 * @param   SimpleXMLElement              $XMLObject  the data source
	 *
	 * @return void modifies the table object
	 */
	protected function setNameAttributes($table, $XMLObject)
	{
		$table->setColumn('abbreviation_de', (string) $XMLObject->kuerzel, '');
		$table->setColumn('abbreviation_en', (string) $XMLObject->kuerzelen, $table->abbreviation_de);
		$table->setColumn('shortName_de', (string) $XMLObject->kurzname, '');
		$table->setColumn('shortName_en', (string) $XMLObject->kurznameen, $table->shortName_de);

		$deTitle = (string) $XMLObject->titelde;
		if (!$enTitle = (string) $XMLObject->titelen)
		{
			$enTitle = $deTitle;
		}

		if (property_exists($table, 'name_de'))
		{
			$table->name_de = $deTitle;
			$table->name_en = $enTitle;
		}
		else
		{
			$table->fullName_de = $deTitle;
			$table->fullName_en = $enTitle;
		}
	}

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

		return (bool) OrganizerHelper::executeQuery('execute');
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

		if (!OrganizerHelper::executeQuery('execute'))
		{
			return false;
		}

		$rgtQuery = $this->_db->getQuery(true);
		$rgtQuery->update('#__organizer_curricula')->set("rgt = rgt - $width")->where("rgt > $left");
		$this->_db->setQuery($rgtQuery);

		return (bool) OrganizerHelper::executeQuery('execute');
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

		if (!OrganizerHelper::executeQuery('execute'))
		{
			return false;
		}

		$rgtQuery = $this->_db->getQuery(true);
		$rgtQuery->update('#__organizer_curricula')->set('rgt = rgt + 2')->where("rgt >= '$left'");
		$this->_db->setQuery($rgtQuery);

		return (bool) OrganizerHelper::executeQuery('execute');
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

		return (bool) OrganizerHelper::executeQuery('execute');
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
		$table = "Organizer\\Tables\\" . $this->helper;

		return new $table();
	}

	/**
	 * Ensures that the title(s) are set and do not contain 'dummy'. This function favors the German title.
	 *
	 * @param   object  $resource  the resource being checked
	 *
	 * @return bool true if one of the titles has the possibility of being valid, otherwise false
	 */
	protected function validTitle($resource)
	{
		$titleDE = trim((string) $resource->titelde);
		$titleEN = trim((string) $resource->titelen);
		$title   = empty($titleDE) ? $titleEN : $titleDE;

		if (empty($title))
		{
			return false;
		}

		$dummyPos = stripos($title, 'dummy');

		return $dummyPos === false;
	}
}