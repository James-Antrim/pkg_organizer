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

use Organizer\Tables;

trait Planned
{
    public static function addUnitDateRestriction($query, $date, $interval)
    {
        switch ($interval) {
            case 'term':
                $term = new Tables\Terms();
                $term->load(Terms::getCurrentID($date));
                $query->where("u.startDate >= '$term->startDate'");
                $query->where("u.endDate <= '$term->endDate'");
                break;
            case 'week':
                $query->where("'$date' BETWEEN u.startDate AND u.endDate");
                break;
            case 'day':
                $query->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
                    ->where("b.date = '$date'");
                break;
        }
    }
}