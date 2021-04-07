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

use Organizer\Adapters\Database;
use Organizer\Tables;

/**
 * Provides general functions for term access checks, data retrieval and display.
 */
class Terms extends ResourceHelper implements Selectable
{
	use Numbered;

	/**
	 * Gets the id of the term whose dates encompass the current date
	 *
	 * @param   string  $date  the reference date
	 *
	 * @return int the id of the term for the dates used on success, otherwise 0
	 */
	public static function getCurrentID($date = ''): int
	{
		$date  = ($date and strtotime($date)) ? date('Y-m-d', strtotime($date)) : date('Y-m-d');
		$query = Database::getQuery();
		$query->select('id')
			->from('#__organizer_terms')
			->where("'$date' BETWEEN startDate and endDate");
		Database::setQuery($query);

		return Database::loadInt();
	}

	/**
	 * Checks for the term end date for a given term id
	 *
	 * @param   int  $termID  the term's id
	 *
	 * @return mixed  string the end date of the term could be resolved, otherwise null
	 */
	public static function getEndDate(int $termID)
	{
		$table = new Tables\Terms();

		return $table->load($termID) ? $table->endDate : null;
	}

	/**
	 * Checks for the term entry in the database, creating it as necessary.
	 *
	 * @param   array  $data  the term's data
	 *
	 * @return mixed  int the id if the room could be resolved/added, otherwise null
	 */
	public static function getID(array $data)
	{
		if (empty($data))
		{
			return null;
		}

		$table        = new Tables\Terms();
		$loadCriteria = ['startDate' => $data['startDate'], 'endDate' => $data['endDate']];

		if ($table->load($loadCriteria))
		{
			return $table->id;
		}

		return $table->save($data) ? $table->id : null;
	}

	/**
	 * Retrieves the ID of the term occurring immediately after the reference term.
	 *
	 * @param   int  $currentID  the id of the reference term
	 *
	 * @return int the id of the subsequent term if successful, otherwise 0
	 */
	public static function getNextID($currentID = 0): int
	{
		if (empty($currentID))
		{
			$currentID = self::getCurrentID();
		}

		$currentEndDate = self::getEndDate($currentID);
		$query          = Database::getQuery(true);
		$query->select('id')
			->from('#__organizer_terms')
			->where("startDate > '$currentEndDate'")
			->order('startDate');
		Database::setQuery($query);

		return Database::loadInt();
	}

	/**
	 * @inheritDoc
	 *
	 * @param   bool  $showDates  if true the start and end date will be displayed as part of the name
	 * @param   bool  $filter     if true only current and future terms will be displayed
	 */
	public static function getOptions($showDates = false, $filter = false): array
	{
		$tag     = Languages::getTag();
		$options = [];

		foreach (Terms::getResources($filter) as $term)
		{
			$name = $term["name_$tag"];

			if ($showDates)
			{
				$startDate = Dates::formatDate($term['startDate']);
				$endDate   = Dates::formatDate($term['endDate']);
				$name      .= " ($startDate - $endDate)";
			}

			$options[] = HTML::_('select.option', $term['id'], $name);
		}

		return $options;
	}

	/**
	 * Retrieves the ID of the term occurring immediately after the reference term.
	 *
	 * @param   int  $currentID  the id of the reference term
	 *
	 * @return int the id of the subsequent term if successful, otherwise 0
	 */
	public static function getPreviousID($currentID = 0): int
	{
		if (empty($currentID))
		{
			$currentID = self::getCurrentID();
		}

		$currentStartDate = self::getStartDate($currentID);
		$query            = Database::getQuery(true);
		$query->select('id')
			->from('#__organizer_terms')
			->where("endDate < '$currentStartDate'")
			->order('endDate DESC');
		Database::setQuery($query);

		return Database::loadInt();
	}

	/**
	 * @inheritDoc
	 *
	 * @params bool $filter
	 */
	public static function getResources($filter = false): array
	{
		$query = Database::getQuery();
		$query->select('DISTINCT term.*')->from('#__organizer_terms AS term')->order('startDate');

		if ($view = Input::getView() and $view === 'Schedules')
		{
			$query->innerJoin('#__organizer_schedules AS s ON s.termID = term.id');
		}

		if ($filter)
		{
			$today = date('Y-m-d');
			$query->where("term.endDate > '$today'");
		}

		Database::setQuery($query);

		return Database::loadAssocList('id');
	}

	/**
	 * Checks for the term start date for a given term id
	 *
	 * @param   int  $termID  the term's id
	 *
	 * @return mixed  string the end date of the term could be resolved, otherwise null
	 */
	public static function getStartDate(int $termID)
	{
		$table = new Tables\Terms();

		return $table->load($termID) ? $table->startDate : null;
	}
}
