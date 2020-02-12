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
use Organizer\Models\Program;
use Organizer\Tables\Participants;
use Organizer\Tables\Programs as ProgramsTable;

/**
 * Provides general functions for program access checks, data retrieval and display.
 */
class Programs extends Curricula implements Selectable
{
	use Filtered;

	/**
	 * Creates a basic query for program related items.
	 *
	 * @return JDatabaseQuery
	 */
	public static function getProgramQuery()
	{
		$dbo        = Factory::getDbo();
		$tag        = Languages::getTag();
		$query      = $dbo->getQuery(true);
		$parts      = ["p.name_$tag", "' ('", 'd.abbreviation', "' '", 'p.accredited', "')'"];
		$nameClause = $query->concatenate($parts, '') . ' AS name';
		$query->select("DISTINCT p.id AS id, $nameClause")
			->from('#__organizer_programs AS p')
			->innerJoin('#__organizer_degrees AS d ON d.id = p.degreeID');

		return $query;
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

		$table = new ProgramsTable;

		return ($table->load($programID) and $organizationID = $table->organizationID) ? $organizationID : 0;
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
		$programTable = new ProgramsTable;
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

		$model     = new Program;
		$programID = $model->save($programData);

		return empty($programID) ? null : $programID;
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
			$name = "{$program['name']} ({$program['degree']},  {$program['accredited']})";

			$options[] = HTML::_('select.option', $program['id'], $name);
		}

		return $options;
	}

	/**
	 * Gets the mapped curricula ranges for the given resource
	 *
	 * @param   mixed  $identifiers  int resourceID | array ranges of subordinate resources
	 *
	 * @return array the resource ranges
	 */
	public static function getRanges($identifiers)
	{
		if (!is_numeric($identifiers) and !is_array($identifiers))
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
			if ($identifiers !== -1)
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
		$query = $dbo->getQuery(true);

		$query->select("p.*, p.name_$tag AS name, d.abbreviation AS degree")
			->from('#__organizer_programs AS p')
			->innerJoin('#__organizer_degrees AS d ON d.id = p.degreeID')
			->innerJoin('#__organizer_mappings AS m ON m.programID = p.id')
			->order('name ASC, degree ASC, accredited DESC');

		if (!empty($access))
		{
			self::addAccessFilter($query, 'p', $access);
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
			$table         = new Participants;
			$exists        = $table->load($participantID);

			if (!$exists)
			{
				$useCurrent = true;
			}
		}

		return $useCurrent;
	}
}
