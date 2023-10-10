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

use THM\Organizer\Adapters\{Application, Database, Text};

/**
 * Provides general functions for campus access checks, data retrieval and display.
 */
class Holidays
{
    public const CLOSED = 2, GAP = 1, HOLIDAY = 3;

    /**
     * Gets holidays occurring between two dates (inclusive).
     *
     * @param string $startDate the start date for the range
     * @param string $endDate   the end date for the range
     *
     * @return array[]
     */
    public static function getRelevant(string $startDate = '', string $endDate = ''): array
    {
        $endDate   = Dates::standardizeDate($endDate);
        $startDate = Dates::standardizeDate($startDate);
        $tag       = Application::getTag();

        $query = Database::getQuery();
        $query->select('*')
            ->from('#__organizer_holidays')
            ->where("startDate >= '$startDate'")
            ->where("endDate <= '$endDate'");
        Database::setQuery($query);

        $holidays = [];
        $results  = Database::loadAssocList();

        for ($currentDT = strtotime($startDate); $currentDT <= strtotime($endDate);) {
            $date            = date('Y-m-d', $currentDT);
            $holidays[$date] = [];

            foreach ($results as $holiday) {
                $hed = $holiday['endDate'];
                $hsd = $holiday['startDate'];

                if ($date >= $hsd and $date <= $hed) {
                    $type = (int) $holiday['type'];

                    if ($type === self::HOLIDAY) {
                        $holidays[$date]['name'] = $holiday["name_$tag"];
                        $holidays[$date]['type'] = 'holiday';
                    } elseif ($type === self::GAP) {
                        $holidays[$date]['name'] = Text::_('ORGANIZER_GAP_DAY');
                        $holidays[$date]['type'] = 'gap';
                    } else {
                        $holidays[$date]['name'] = Text::_('ORGANIZER_CLOSED_DAY');
                        $holidays[$date]['type'] = 'closed';
                    }
                }
            }

            $currentDT = strtotime("+1 day", $currentDT);
        }

        return $holidays;
    }
}
