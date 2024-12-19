<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

use DateTime;
use Joomla\Database\DatabaseQuery;
use THM\Organizer\Adapters\{Database as DB, Input, Text};

/**
 * Class provides generalized functions regarding dates and times.
 */
class Dates
{
    /**
     * Modifies a query with a restriction for a value (not) between two column values.
     *
     * @param   DatabaseQuery  $query   the query to modify
     * @param   string         $column  the column for the restriction
     * @param   string         $low     the low date value
     * @param   string         $high    the high date value
     * @param   bool           $not     whether the restriction should be negated
     *
     * @return void
     */
    public static function betweenValues(DatabaseQuery $query, string $column, string $low, string $high, bool $not = false): void
    {
        $column = DB::qn($column);
        [$low, $high] = DB::quote([$low, $high]);
        $where = $not ? "$column NOT BETWEEN $low AND $high" : "$column BETWEEN $low AND $high";
        $query->where($where);
    }

    /**
     * Formats the date stored in the database according to the format in the component parameters
     *
     * @param   string  $date      the date to be formatted
     * @param   bool    $withText  if the day name should be part of the output
     * @param   bool    $short     if the day name output should be abbreviated
     *
     * @return string
     */
    public static function formatDate(string $date = '', bool $withText = false, bool $short = false): string
    {
        $date          = empty($date) ? date('Y-m-d') : $date;
        $formattedDate = date(self::formatParameter(), strtotime($date));

        if ($withText) {
            $textFormat    = $short ? 'D' : 'l';
            $shortDOW      = date($textFormat, strtotime($date));
            $text          = Text::_(strtoupper($shortDOW));
            $formattedDate = "$text $formattedDate";
        }

        return $formattedDate;
    }

    /**
     * Converts a raw date time into a formatted date time string.
     *
     * @param   int|string  $dateTime  the raw date time
     *
     * @return string
     */
    public static function formatDateTime(int|string $dateTime): string
    {
        $format   = self::formatParameter() . ' H:i';
        $dateTime = is_string($dateTime) ? strtotime($dateTime) : $dateTime;

        return date($format, $dateTime);
    }

    /**
     * Formats the date stored in the database according to the format in the component parameters
     *
     * @param   string  $time  the date to be formatted
     *
     * @return string
     */
    public static function formatEndTime(string $time): string
    {
        return date('H:i', strtotime('+1 minute', strtotime($time)));
    }

    /**
     * Gets the format from the component settings.
     *
     * @return string
     */
    public static function formatParameter(): string
    {
        return Input::getParams()->get('dateFormat', 'd.m.Y');
    }

    /**
     * Formats the date stored in the database according to the format in the component parameters
     *
     * @param   string  $time  the date to be formatted
     *
     * @return string
     */
    public static function formatTime(string $time): string
    {
        return date('H:i', strtotime($time));
    }

    /**
     * Formats the date stored in the database according to the format in the component parameters
     *
     * @param   string  $startDate  the start date of the resource
     * @param   string  $endDate    the end date of the resource
     *
     * @return string
     */
    public static function intervalText(string $startDate, string $endDate): string
    {
        $startDate = self::formatDate($startDate);
        $endDate   = self::formatDate($endDate);

        return $startDate === $endDate ? $startDate : "$startDate - $endDate";
    }

    /**
     * Returns the end and start dates of a three-month period beginning with the date given.
     *
     * @param   string  $date  the date
     * @param   int     $startDay
     *
     * @return string[]
     */
    public static function ninetyDays(string $date, int $startDay = 1): array
    {
        $dateTime = strtotime($date);

        switch (Input::getCMD('format')) {
            case 'pdf':
                $startDayName = date('l', strtotime("Sunday + $startDay days"));
                $dateTime     = strtotime("$startDayName this week", $dateTime);
                break;
            default:
                break;
        }

        return ['startDate' => date('Y-m-d', $dateTime), 'endDate' => date('Y-m-d', strtotime('+90 days', $dateTime))];
    }

    /**
     * Returns the end date and start date of the month for the given date
     *
     * @param   string  $date  the date
     *
     * @return string[]
     */
    public static function oneMonth(string $date): array
    {
        $dateTime = strtotime($date);
        $endDT    = strtotime('last day of this month', $dateTime);
        $startDT  = strtotime('first day of this month', $dateTime);

        return ['startDate' => date('Y-m-d', $startDT), 'endDate' => date('Y-m-d', $endDT)];
    }

    /**
     * Returns the end and start dates of a six-month period beginning with the date given.
     *
     * @param   string  $date  the date
     *
     * @return string[]
     */
    public static function sixMonths(string $date): array
    {
        $dateTime = strtotime($date);

        return ['startDate' => date('Y-m-d', $dateTime), 'endDate' => date('Y-m-d', strtotime('+6 month', $dateTime))];
    }

    /**
     * Converts a date string from the format in the component settings into the format used by the database
     *
     * @param   string  $date  the date string
     *
     * @return string
     */
    public static function standardize(string $date = ''): string
    {
        $default = date('Y-m-d');

        if (empty($date)) {
            return $default;
        }

        if (self::standardized($date)) {
            return $date;
        }

        $dt = DateTime::createFromFormat(self::formatParameter(), $date);

        return ($dt !== false and !array_sum($dt::getLastErrors())) ? $dt->format('Y-m-d') : $default;
    }

    /**
     * Checks whether a date is a valid date in the standard Y-m-d format.
     *
     * @param   string  $date  the date to be checked
     *
     * @return bool
     */
    public static function standardized(string $date): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d', $date);

        return ($dt !== false and $dt::getLastErrors() === false);
    }

    /**
     * Returns the end date and start date of the term for the given date
     *
     * @param   string  $date  the date in format Y-m-d
     *
     * @return string[]
     */
    public static function term(string $date): array
    {
        $query = DB::query();
        $query->select(DB::qn(['startDate', 'endDate']))->from(DB::qn('#__organizer_terms'));
        DB::between($query, $date, 'startDate', 'endDate');
        DB::set($query);

        return DB::array();
    }

    /**
     * Checks whether the string is a valid date in the Y-m-d format.
     *
     * @param   string  $date  the date to validate
     *
     * @return bool
     */
    public static function validate(string $date): bool
    {
        $pieces = explode('-', $date);

        if (count($pieces) !== 3) {
            return false;
        }

        [$year, $month, $day] = $pieces;

        return checkdate($month, $day, $year);
    }

    /**
     * Returns the end date and start date of the week for the given date
     *
     * @param   string  $date      the date
     * @param   int     $startDay  0-6 number of the starting day of the week
     * @param   int     $endDay    0-6 number of the ending day of the week
     *
     * @return string[]
     */
    public static function week(string $date, int $startDay = 1, int $endDay = 6): array
    {
        $dateTime     = strtotime($date);
        $startDayName = date('l', strtotime("Sunday + $startDay days"));
        $endDayName   = date('l', strtotime("Sunday + $endDay days"));
        $startDate    = date('Y-m-d', strtotime("$startDayName this week", $dateTime));
        $endDate      = date('Y-m-d', strtotime("$endDayName this week", $dateTime));

        return ['startDate' => $startDate, 'endDate' => $endDate];
    }
}
