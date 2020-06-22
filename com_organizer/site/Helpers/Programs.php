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

use Exception;
use JDatabaseQuery;
use Joomla\CMS\Factory;
use Organizer\Models;
use Organizer\Tables;

/**
 * Provides general functions for program access checks, data retrieval and display.
 */
class Programs extends Curricula implements Selectable
{
	static protected $resource = 'program';

	/**
	 * Gets a HTML option based upon a program curriculum association
	 *
	 * @param   array   $range      the program curriculum range
	 * @param   array   $parentIDs  the selected parents
	 * @param   string  $type       the resource type of the form
	 *
	 * @return string  HTML option
	 */
	public static function getCurricularOption($range, $parentIDs, $type)
	{
		$dbo   = Factory::getDbo();
		$query = self::getQuery();
		$query->where("p.id = {$range['programID']}");
		$dbo->setQuery($query);

		if (!$program = OrganizerHelper::executeQuery('loadAssoc', []))
		{
			return '';
		}

		$selected = in_array($range['id'], $parentIDs) ? 'selected' : '';
		$disabled = $type === 'pool' ? '' : 'disabled';

		return "<option value='{$range['id']}' $selected $disabled>{$program['name']}</option>";
	}

	/**
	 * Attempts to get the real program's id, creating the stub if non-existent.
	 *
	 * @param   array   $programData  the program data
	 * @param   string  $initialName  the name to be used if no entry already exists
	 *
	 * @return mixed int on success, otherwise null
	 * @throws Exception
	 */
	public static function getID($programData, $initialName)
	{
		$programTable = new Tables\Programs;
		if ($programTable->load($programData))
		{
			return $programTable->id;
		}

		if (empty($initialName))
		{
			return null;
		}

		$programData['organizationID'] = Input::getInt('organizationID');
		$programData['name_de']        = $initialName;
		$programData['name_en']        = $initialName;

		$model     = new Models\Program;
		$programID = $model->save($programData);

		return empty($programID) ? null : $programID;
	}

	/**
	 * Gets the programIDs for the given resource
	 *
	 * @param   mixed  $identifiers  int resourceID | array ranges of subordinate resources
	 *
	 * @return array the program ids
	 */
	public static function getIDs($identifiers)
	{
		if (!$ranges = self::getRanges($identifiers))
		{
			return [];
		}

		$ids = [];
		foreach ($ranges as $range)
		{
			$ids[] = $range['programID'];
		}

		$ids = array_unique($ids);
		sort($ids);

		return $ids;
	}

	/**
	 * Retrieves the program name
	 *
	 * @param   int  $programID  the table id for the program
	 *
	 * @return string the name of the (plan) program, otherwise empty
	 */
	public static function getName($programID)
	{
		if (empty($programID))
		{
			return Languages::_('ORGANIZER_NO_PROGRAM');
		}

		$dbo = Factory::getDbo();
		$tag = Languages::getTag();

		$query     = $dbo->getQuery(true);
		$nameParts = ["p.name_$tag", "' ('", 'd.abbreviation', "' '", 'p.accredited', "')'"];
		$query->select($query->concatenate($nameParts, "") . ' AS name')
			->from('#__organizer_programs AS p')
			->innerJoin('#__organizer_degrees AS d ON d.id = p.degreeID')
			->where("p.id = '$programID'");

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadResult', '');
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
		foreach (self::getResources($access) as $program)
		{
			$options[] = HTML::_('select.option', $program['id'], $program['name']);
		}

		return $options;
	}

	/**
	 * Retrieves the organizationIDs associated with the program
	 *
	 * @param   int  $programID  the table id for the program
	 *
	 * @return int the organizationID associated with the program's documentation
	 */
	public static function getOrganization($programID)
	{
		if (empty($programID))
		{
			return Languages::_('ORGANIZER_NO_ORGANIZATION');
		}

		$table = new Tables\Programs;

		return ($table->load($programID) and $organizationID = $table->organizationID) ? $organizationID : 0;
	}

	/**
	 * Creates a basic query for program related items.
	 *
	 * @return JDatabaseQuery
	 */
	public static function getQuery()
	{
		$dbo        = Factory::getDbo();
		$tag        = Languages::getTag();
		$query      = $dbo->getQuery(true);
		$parts      = ["p.name_$tag", "' ('", 'd.abbreviation', "', '", 'p.accredited', "')'"];
		$nameClause = $query->concatenate($parts, '') . ' AS name';
		$query->select("DISTINCT p.id AS id, $nameClause")
			->from('#__organizer_programs AS p')
			->innerJoin('#__organizer_degrees AS d ON d.id = p.degreeID');

		return $query;
	}

	/**
	 * Gets the mapped curricula ranges for the given resource. Returns array of associations for compatibility reasons.
	 *
	 * @param   mixed  $identifiers  int resourceID | array ranges of subordinate resources
	 *
	 * @return array the resource ranges
	 */
	public static function getRanges($identifiers)
	{
		if (empty($identifiers) or (!is_numeric($identifiers) and !is_array($identifiers)))
		{
			return [];
		}

		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('DISTINCT *')
			->from('#__organizer_curricula')
			->where('programID IS NOT NULL ')
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
				$query->where("programID = $programID");
			}
		}

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
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
		$dbo   = Factory::getDbo();
		$tag   = Languages::getTag();
		$query = self::getQuery();

		$query->select("d.abbreviation AS degree")
			->innerJoin('#__organizer_curricula AS c ON c.programID = p.id')
			->order('name');

		if (!empty($access))
		{
			self::addAccessFilter($query, $access, 'program', 'p');
		}

		self::addResourceFilter($query, 'organization', 'o', 'p');

		$useCurrent = self::useCurrent();
		if ($useCurrent)
		{
			$subQuery = $dbo->getQuery(true);
			$subQuery->select("p2.name_$tag, p2.degreeID, MAX(p2.accredited) AS accredited")
				->from('#__organizer_programs AS p2')
				->group("p2.name_$tag, p2.degreeID");
			$conditions = "grouped.name_$tag = p.name_$tag ";
			$conditions .= "AND grouped.degreeID = p.degreeID ";
			$conditions .= "AND grouped.accredited = p.accredited ";
			$query->innerJoin("($subQuery) AS grouped ON $conditions");
		}

		$dbo->setQuery($query);

		return OrganizerHelper::executeQuery('loadAssocList', []);
	}

	/**
	 * Looks up the names of the programs associated with the resource
	 *
	 * @param   array  $programIDs  the ids of the program resources
	 *
	 * @return array the program ranges
	 */
	public static function getPrograms($programIDs)
	{
		$ranges = [];
		foreach ($programIDs as $programID)
		{
			$ranges = array_merge($ranges, self::getRanges($programID));
		}

		return $ranges;
	}

	/**
	 * Determines whether only the latest accreditation version of a program should be displayed in the list.
	 *
	 * @return bool
	 */
	private static function useCurrent()
	{
		$useCurrent  = false;
		$view        = Input::getView();
		$selectedIDs = Input::getSelectedIDs();
		if ($view === 'participant_edit')
		{
			$participantID = empty($selectedIDs) ? Factory::getUser() : $selectedIDs[0];
			$table         = new Tables\Participants;
			$exists        = $table->load($participantID);

			if (!$exists)
			{
				$useCurrent = true;
			}
		}

		return $useCurrent;
	}
}
