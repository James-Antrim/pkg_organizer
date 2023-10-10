<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\JSON;

use THM\Organizer\Adapters\Input;

trait Planned
{
    /**
     * Resolves the date.
     * @return false|string
     */
    public function getDate()
    {
        $date = Input::getString('date');

        return ($dts = strtotime($date)) ? date('Y-m-d', $dts) : date('Y-m-d');
    }

    /**
     * Resolves the interval.
     * @return string
     */
    public function getInterval(): string
    {
        $intervals = ['day', 'week', 'term'];
        $interval  = Input::getString('interval');

        return in_array($interval, $intervals) ? $interval : 'term';
    }
}