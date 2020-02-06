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
use Joomla\CMS\Factory;
use Organizer\Models\Program;
use Organizer\Tables\Participants;
use Organizer\Tables\Programs as ProgramsTable;

/**
 * Provides general functions for program access checks, data retrieval and display.
 */
class Programs extends ResourceHelper implements Selectable
{
	use Filtered;

	/**
	 * Retrieves the departmentIDs associated with the program
	 *
	 * @param   int  $programID  the table id for the program
	 *
	 * @return int the departmentID associated with the program's documentation
	 */
	public static function getDepartment($programID)
	{
		if (empty($programID))
		{
			return Languages::_('ORGANIZER_NO_ORGANIZATION');
		}

		$table = new ProgramsTable;

		return ($table->load($programID) and $departmentID = $table->departmentID) ? $departmentID : 0;
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

		$programData['departmentID'] = Input::getInt('departmentID');
		$programData['name_de']      = $initialName;
		$programData['name_en']      = $initialName;

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

		$query->select("dp.*, dp.name_$tag AS name, d.abbreviation AS degree")
			->from('#__organizer_programs AS dp')
			->innerJoin('#__organizer_degrees AS d ON d.id = dp.degreeID')
			->innerJoin('#__organizer_mappings AS m ON m.programID = dp.id')
			->order('name ASC, degree ASC, accredited DESC');

		if (!empty($access))
		{
			self::addAccessFilter($query, 'dp', $access);
		}

		self::addResourceFilter($query, 'department', 'dept', 'dp');

		$useCurrent = self::useCurrent();
		if ($useCurrent)
		{
			$subQuery = $dbo->getQuery(true);
			$subQuery->select("dp2.name_$tag, dp2.degreeID, MAX(dp2.accredited) AS accredited")
				->from('#__organizer_programs AS dp2')
				->group("dp2.name_$tag, dp2.degreeID");
			$conditions = "grouped.name_$tag = dp.name_$tag ";
			$conditions .= "AND grouped.degreeID = dp.degreeID ";
			$conditions .= "AND grouped.accredited = dp.accredited ";
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
