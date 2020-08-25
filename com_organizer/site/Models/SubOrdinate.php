<?php
/**
 * @category    Joomla component
 * @package     THM_Organizer
 * @subpackage  com_thm_organizer.<subdirectory>
 * @name        SubOrdinate
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.2
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Joomla\Utilities\ArrayHelper;
use Organizer\Helpers;
use Organizer\Tables\Curricula;

trait SubOrdinate
{
	/**
	 * Adds ranges for the resource to the given superordinate ranges.
	 *
	 * @param   array  $data            the resource data from the form
	 * @param   array  $superOrdinates  the valid superordinate ranges to which to create/validate ranges within
	 *
	 * @return bool
	 */
	private function addNew($data, $superOrdinates)
	{
		$existingRanges = $this->getRanges($data['id']);
		$resourceColumn = $this->resource . 'ID';
		$range          = [
			$resourceColumn => $data['id'],
			'curriculum'    => $this->resource === 'pool' ? $this->getSubOrdinates() : []
		];

		foreach ($superOrdinates as $superOrdinate)
		{
			$range['parentID'] = $superOrdinate['id'];

			foreach ($existingRanges as $eIndex => $eRange)
			{
				// There is an existing relationship
				if ($eRange['lft'] > $superOrdinate['lft'] and $eRange['rgt'] < $superOrdinate['rgt'])
				{
					// Prevent further iteration of an established relationship
					unset($existingRanges[$eIndex]);

					// Update subordinate curricula entries as necessary
					foreach ($range['curriculum'] as $subOrdinate)
					{
						$subOrdinate['parentID'] = $eRange['id'];

						$this->addRange($subOrdinate);
					}

					continue 2;
				}
			}

			$range['ordering'] = $this->getOrdering($superOrdinate['id'], $data['id']);

			if (!$this->addRange($range))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Performs checks to ensure that a superordinate item has been selected as a precursor to the rest of the
	 * curriculum processing.
	 *
	 * @param   array  $data  the form data
	 *
	 * @return array the applicable superordinate ranges
	 */
	private function getSuperOrdinates($data)
	{
		// No need to check superordinates if no curriculum was selected
		if (empty($data['curricula']))
		{
			$this->deleteRanges($data['id']);

			return [];
		}

		$data['curricula'] = ArrayHelper::toInteger($data['curricula']);

		if (array_search(self::NONE, $data['curricula']) !== false)
		{
			$this->deleteRanges($data['id']);

			return [];
		}

		if (empty($data['superordinates']) or array_search(self::NONE, $data['superordinates']) !== false)
		{
			$this->deleteRanges($data['id']);

			return [];
		}

		// Retrieve the program ranges for sanity checks on the pool ranges
		$programRanges = [];
		foreach ($data['curricula'] as $programID)
		{
			if ($ranges = Helpers\Programs::getRanges($programID))
			{
				$programRanges[$programID] = $ranges[0];
			}
		}

		$superOrdinateRanges = [];
		foreach ($data['superordinates'] as $superOrdinateID)
		{
			$table = new Curricula();

			// Non-existent or invalid entry
			if (!$table->load($superOrdinateID) or $table->subjectID)
			{
				continue;
			}

			if ($programID = $table->programID)
			{
				// Subjects may not be directly associated with programs.
				if ($this->resource === 'subject')
				{
					continue;
				}

				foreach ($programRanges as $programRange)
				{
					if ($programRange['programID'] === $programID)
					{
						$superOrdinateRanges[$programRange['id']] = $programRange;
					}
				}

				continue;
			}

			foreach (Helpers\Pools::getRanges($table->poolID) as $poolRange)
			{
				foreach ($programRanges as $programRange)
				{
					if ($poolRange['lft'] > $programRange['lft'] and $poolRange['rgt'] < $programRange['rgt'])
					{
						$superOrdinateRanges[$poolRange['id']] = $poolRange;
					}
				}
			}
		}

		return $superOrdinateRanges;
	}

	/**
	 * Removes resource ranges not subordinate to the given superordinate elements.
	 *
	 * @param   int    $resourceID      the resource id
	 * @param   array  $superOrdinates  the valid superordinate ranges
	 *
	 * @return void removes deprecated ranges from the database
	 */
	private function removeDeprecated($resourceID, $superOrdinates)
	{
		foreach ($this->getRanges($resourceID) as $range)
		{
			foreach ($superOrdinates as $index => $superOrdinate)
			{
				// The range boundaries will have changed after an add => re-initiate the range with fresh data.
				$superOrdinate = Helpers\Curricula::getRange($superOrdinate['id']);

				// Relationship requested and established
				if ($range['lft'] > $superOrdinate['lft'] and $range['rgt'] < $superOrdinate['rgt'])
				{
					// Prevent further iteration of an established relationship
					unset($superOrdinates[$index]);
					continue 2;
				}
			}

			// Remove unrequested existing relationship
			$this->deleteRange($range['id']);
		}
	}
}