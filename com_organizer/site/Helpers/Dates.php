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
    public const SUNDAY = 0;

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
        return Input::parameters()->get('dateFormat', 'd.m.Y');
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
     * Calculates the start and end dates of a month.
     *
     * @param   int  $dateTime  the datetime reference to calculate the dates with
     *
     * @return array
     */
    public static function month(int $dateTime): array
    {
        return [
            date('Y-m-d', strtotime('first day of this month', $dateTime)),
            date('Y-m-d', strtotime('last day of this month', $dateTime))
        ];
    }

    /**
     * Calculates the start and end dates of a month.
     *
     * @param   int  $dateTime  the datetime reference to calculate the dates with
     *
     * @return array
     */
    public static function ninetyDays(int $dateTime): array
    {
        if (Input::cmd('format') === Input::PDF) {
            $dateTime = strtotime("Monday this week", $dateTime);
        }

        return [date('Y-m-d', $dateTime), date('Y-m-d', strtotime('+90 days', $dateTime))];
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
     * Calculates the start and end dates of week.
     *
     * @param   int  $dateTime  the datetime reference to calculate the dates with
     *
     * @return array
     */
    public static function week(int $dateTime): array
    {
        return [
            date('Y-m-d', strtotime("Monday this week", $dateTime)),
            date('Y-m-d', strtotime("Saturday this week", $dateTime))
        ];
    }
}
