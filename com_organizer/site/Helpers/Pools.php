<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;

use Joomla\CMS\Factory;
use Organizer\Tables;

/**
 * Provides general functions for (subject) pool access checks, data retrieval and display.
 */
class Pools extends Curricula implements Selectable
{
	use Filtered;

	/**
	 * Creates a text for the required pool credit points
	 *
	 * @param   object  $pool  the pool
	 *
	 * @return string  the required amount of credit points
	 */
	public static function getCrPText($pool)
	{
		$minCrPExists = !empty($pool['minCrP']);
		$maxCrPExists = !empty($pool['maxCrP']);
		if ($maxCrPExists and $minCrPExists)
		{
			return $pool['maxCrP'] === $pool['minCrP'] ?
				"{$pool['maxCrP']} CrP" : "{$pool['minCrP']} - {$pool['maxCrP']} CrP";
		}
		else
		{
			if ($maxCrPExists)
			{
				return "max. {$pool['maxCrP']} CrP";
			}
			elseif ($minCrPExists)
			{
				return "min. {$pool['minCrP']} CrP";
			}
		}

		return '';
	}

	/**
	 * Gets a HTML option based upon a pool curriculum association
	 *
	 * @param   array  $range      the curriculum range entry
	 * @param   array  $parentIDs  the selected parents
	 *
	 * @return string  HTML option
	 */
	public static function getCurricularOption($range, $parentIDs)
	{
		$tag        = Languages::getTag();
		$poolsTable = new Tables\Pools;
		$poolsTable->load($range['poolID']);

		if (!$poolsTable->load($range['poolID']))
		{
			return '';
		}

		$nameColumn   = "name_$tag";
		$indentedName = Pools::getIndentedName($poolsTable->$nameColumn, $range['level']);

		$selected = in_array($range['id'], $parentIDs) ? 'selected' : '';

		return "<option value='{$range['id']}' $selected>$indentedName</option>";
	}

	/**
	 * Retrieves the pool's full name if existent.
	 *
	 * @param   int  $poolID  the table's pool id
	 *
	 * @return string the full name, otherwise an empty string
	 */
	public static function getFullName($poolID)
	{
		$table = new Tables\Pools;

		return $table->load($poolID) ? $table->fullName : '';
	}

	/**
	 * Creates a name for use in a list of options implicitly displaying the pool hierarchy.
	 *
	 * @param   string  $name   the name of the pool
	 * @param   int     $level  the structural depth
	 *
	 * @return string the pool name indented according to the curricular hierarchy
	 */
	public static function getIndentedName($name, $level)
	{
		$iteration = 0;
		$indent    = '';
		while ($iteration < $level)
		{
			$indent .= '&nbsp;&nbsp;&nbsp;';
			$iteration++;
		}

		return $indent . '|_' . $name;
	}

	/**
	 * Retrieves the selectable options for the resource.
	 *
	 * @param   string  $access  any access restriction which should be performed
	 *
	 * @return array the available options
	 */
	public static function getOptions($access = '')
	{
		$options = [];
		foreach (self::getResources($access) as $pool)
		{
			$options[] = HTML::_('select.option', $pool['id'], $pool['name']);
		}

		return $options;
	}

	/**
	 * Retrieves pool options for a given curriculum element
	 *
	 * @return string
	 */
	public static function getParentOptions()
	{
		$resourceID   = Input::getID();
		$resourceType = Input::getCMD('type');

		// Pending program ranges are dependant on selected programs.
		$programIDs    = Input::getFilterIDs('program');
		$programRanges = Programs::getPrograms($programIDs);

		$options = self::getSuperOrdinateOptions($resourceID, $resourceType, $programRanges);

		return implode('', $options);
	}

	/**
	 * Gets the mapped curricula ranges for the given pool
	 *
	 * @param   mixed  $identifiers  int poolID | array ranges of subordinate resources
	 * @param   bool   $exclude      whether subordinate pools should be filtered from the result set
	 *
	 * @return array the pool ranges
	 */
	public static function getRanges($identifiers, $exclude = true)
	{
		if (!is_numeric($identifiers) and !is_array($identifiers))
		{
			return [];
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('DISTINCT *')
			->from('#__organizer_curricula')
			->where('poolID IS NOT NULL ')
			->order('lft');

		if (is_array($identifiers))
		{
			self::filterSuperOrdinate($query, $identifiers);
		}
		else
		{
			$programID = (int) $identifiers;
			if ($identifiers != self::NONE)
			{
				$query->where("poolID = $programID");
			}
		}

		$dbo->setQuery($query);

		$ranges = OrganizerHelper::executeQuery('loadAssocList', []);

		if ($exclude)
		{
			$filteredBoundaries = [];
			foreach ($ranges as $range)
			{
				$filteredBoundaries = self::removeExclusions($range);
			}

			return $filteredBoundaries;
		}

		return $ranges;
	}

	/**
	 * Gets an array modelling the attributes of the resource.
	 *
	 * @param $resourceID
	 *
	 * @return array
	 */
	public static function getResource($resourceID)
	{
		$table = new Tables\Pools;

		if (!$table->load($resourceID))
		{
			return [];
		}

		$tag = Languages::getTag();

		return [
			'abbreviation' => $table->{"abbreviation_$tag"},
			'bgColor'      => Fields::getColor($table->fieldID),
			'description'  => $table->{"description_$tag"},
			'field'        => Fields::getName($table->fieldID),
			'fieldID'      => $table->fieldID,
			'id'           => $table->id,
			'maxCrP'       => $table->maxCrP,
			'minCrP'       => $table->minCrP,
			'name'         => $table->{"name_$tag"},
			'shortName'    => $table->{"shortName_$tag"},
		];
	}

	/**
	 * Retrieves the resource items.
	 *
	 * @param   string  $access  any access restriction which should be performed
	 *
	 * @return array the available resources
	 */
	public static function getResources($access = '')
	{
		$programID = Input::getFilterID('program');
		$poolID    = Input::getInput()->get->getInt('poolID');
		if (empty($programID) and empty($poolID))
		{
			return [];
		}

		$ranges = $poolID ? self::getRanges($poolID) : Programs::getRanges($programID);
		if (empty($ranges))
		{
			return [];
		}

		$tag   = Languages::getTag();
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select("DISTINCT p.*, p.name_$tag AS name")
			->from('#__organizer_pools AS p')
			->innerJoin('#__organizer_curricula AS c ON c.poolID = p.id')
			->where("lft > '{$ranges[0]['lft']}'")
			->where("rgt < '{$ranges[0]['rgt']}'")
			->order('name ASC');

		if (!empty($access))
		{
			self::addAccessFilter($query, 'p', $access);
		}

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
	}

	/**
	 * Retrieves the range of the selected resource exclusive subordinate pools.
	 *
	 * @param   int  $range  the original range of a pool
	 *
	 * @return array  array of arrays with boundary values
	 */
	private static function removeExclusions($range)
	{
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('lft, rgt')->from('#__organizer_curricula')
			->where('poolID IS NOT NULL')
			->where("lft > '{$range['lft']}' AND rgt < '{$range['rgt']}'")
			->order('lft');
		$dbo->setQuery($query);

		if (!$exclusions = OrganizerHelper::executeQuery('loadAssocList'))
		{
			return [$range];
		}

		$ranges = [];
		foreach ($exclusions as $exclusion)
		{
			// Subordinate has no own subordinates => has no impact on output
			if ($exclusion['lft'] + 1 == $exclusion['rgt'])
			{
				continue;
			}

			// Not an immediate subordinate
			if ($exclusion['lft'] != $range['lft'] + 1)
			{
				// Create a new boundary from the current left to the exclusion
				$boundary = ['lft' => $range['lft'], 'rgt' => $exclusion['lft']];

				// Change the new left to the other side of the exclusion
				$range['lft'] = $exclusion['rgt'];

				$ranges[] = $boundary;
				continue;
			}

			// Change the new left to the other side of the exclusion
			$range['lft'] = $exclusion['rgt'];

			if ($range['lft'] >= $range['rgt'])
			{
				break;
			}
		}

		// Remnants after exclusions still exist
		if ($range['lft'] < $range['rgt'])
		{
			$ranges[] = $range;
		}

		return $ranges;
	}
}
