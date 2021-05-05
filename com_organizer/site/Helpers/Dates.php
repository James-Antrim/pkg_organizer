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

use DateTime;
use Organizer\Adapters\Database;

/**
 * Class provides generalized functions regarding dates and times.
 */
class Dates
{
	/**
	 * Formats the date stored in the database according to the format in the component parameters
	 *
	 * @param   string  $date      the date to be formatted
	 * @param   bool    $withText  if the day name should be part of the output
	 * @param   bool    $short     if the day name output should be abbreviated
	 *
	 * @return string|bool  a formatted date string otherwise false
	 */
	public static function formatDate($date = '', $withText = false, $short = false)
	{
		$date          = empty($date) ? date('Y-m-d') : $date;
		$formattedDate = date(self::getFormat(), strtotime($date));

		if ($withText)
		{
			$textFormat    = $short ? 'D' : 'l';
			$shortDOW      = date($textFormat, strtotime($date));
			$text          = Languages::_(strtoupper($shortDOW));
			$formattedDate = "$text $formattedDate";
		}

		return $formattedDate;
	}

	/**
	 * Formats the date stored in the database according to the format in the component parameters
	 *
	 * @param   string  $time  the date to be formatted
	 *
	 * @return string|bool  a formatted date string otherwise false
	 */
	public static function formatEndTime(string $time)
	{
		return date('H:i', strtotime('+1 minute', strtotime($time)));
	}

	/**
	 * Formats the date stored in the database according to the format in the component parameters
	 *
	 * @param   string  $time  the date to be formatted
	 *
	 * @return string|bool  a formatted date string otherwise false
	 */
	public static function formatTime(string $time)
	{
		return date('H:i', strtotime($time));
	}

	/**
	 * Formats the date stored in the database according to the format in the component parameters
	 *
	 * @param   string  $startDate  the start date of the resource
	 * @param   string  $endDate    the end date of the resource
	 * @param   bool    $withText   if the day name should be part of the output
	 * @param   bool    $short      if the day name output should be abbreviated
	 *
	 * @return string|bool  a formatted date string otherwise false
	 */
	public static function getDisplay(string $startDate, string $endDate, $withText = false, $short = false)
	{
		$startDate = self::formatDate($startDate, $withText, $short);
		$endDate   = self::formatDate($endDate, $withText, $short);

		return $startDate === $endDate ? $startDate : "$startDate - $endDate";
	}

	/**
	 * Gets the format from the component settings
	 *
	 * @return string the date format
	 */
	public static function getFormat(): string
	{
		return Input::getParams()->get('dateFormat', 'd.m.Y');
	}

	/**
	 * Returns the end and start dates of a six month period beginning with the date given.
	 *
	 * @param   string  $date  the date
	 *
	 * @return array containing startDate and endDate
	 */
	public static function getHalfYear(string $date): array
	{
		$dateTime = strtotime($date);

		return ['startDate' => date('Y-m-d', $dateTime), 'endDate' => date('Y-m-d', strtotime('+6 month', $dateTime))];
	}

	/**
	 * Returns the end date and start date of the month for the given date
	 *
	 * @param   string  $date  the date
	 *
	 * @return array containing startDate and endDate
	 */
	public static function getMonth(string $date): array
	{
		$dateTime  = strtotime($date);
		$startDate = date('Y-m-d', strtotime('first day of this month', $dateTime));
		$endDate   = date('Y-m-d', strtotime('last day of this month', $dateTime));

		return ['startDate' => $startDate, 'endDate' => $endDate];
	}

	/**
	 * Returns the end and start dates of a three month period beginning with the date given.
	 *
	 * @param   string  $date  the date
	 * @param   int     $startDay
	 *
	 * @return array containing startDate and endDate
	 */
	public static function getQuarter(string $date, $startDay = 1): array
	{
		switch (Input::getCMD('format'))
		{
			case 'pdf':
				$dateTime     = strtotime($date);
				$startDayName = date('l', strtotime("Sunday + $startDay days"));
				$dateTime     = strtotime("$startDayName this week", $dateTime);
				break;
			default:
				$dateTime = strtotime($date);
				break;
		}

		return ['startDate' => date('Y-m-d', $dateTime), 'endDate' => date('Y-m-d', strtotime('+3 month', $dateTime))];
	}

	/**
	 * Returns the end date and start date of the term for the given date
	 *
	 * @param   string  $date  the date in format Y-m-d
	 *
	 * @return array containing startDate and endDate
	 */
	public static function getTerm(string $date): array
	{
		$query = Database::getQuery();
		$query->select('startDate, endDate')
			->from('#__organizer_terms')
			->where("'$date' BETWEEN startDate AND endDate");
		Database::setQuery($query);

		return Database::loadAssoc();
	}

	/**
	 * Returns the end date and start date of the week for the given date
	 *
	 * @param   string  $date      the date
	 * @param   int     $startDay  0-6 number of the starting day of the week
	 * @param   int     $endDay    0-6 number of the ending day of the week
	 *
	 * @return array containing startDate and endDate
	 */
	public static function getWeek(string $date, $startDay = 1, $endDay = 6): array
	{
		$dateTime     = strtotime($date);
		$startDayName = date('l', strtotime("Sunday + $startDay days"));
		$endDayName   = date('l', strtotime("Sunday + $endDay days"));
		$startDate    = date('Y-m-d', strtotime("$startDayName this week", $dateTime));
		$endDate      = date('Y-m-d', strtotime("$endDayName this week", $dateTime));

		return ['startDate' => $startDate, 'endDate' => $endDate];
	}

	/**
	 * Checks whether a date is a valid date in the standard Y-m-d format.
	 *
	 * @param   string  $date  the date to be checked
	 *
	 * @return bool
	 */
	public static function isStandardized(string $date): bool
	{
		$dt = DateTime::createFromFormat('Y-m-d', $date);

		return ($dt !== false and !array_sum($dt->getLastErrors()));
	}

	/**
	 * Converts a date string from the format in the component settings into the format used by the database
	 *
	 * @param   string  $date  the date string
	 *
	 * @return string  date sting in format Y-m-d
	 */
	public static function standardizeDate($date = ''): string
	{
		$default = date('Y-m-d');

		if (empty($date))
		{
			return $default;
		}

		if (self::isStandardized($date))
		{
			return $date;
		}

		$dt = DateTime::createFromFormat(self::getFormat(), $date);

		return ($dt !== false and !array_sum($dt->getLastErrors())) ? $dt->format('Y-m-d') : $default;
	}
}
