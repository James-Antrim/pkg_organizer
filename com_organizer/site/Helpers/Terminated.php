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

use Joomla\Database\DatabaseQuery;
use THM\Organizer\Adapters\Database as DB;
use THM\Organizer\Tables;

trait Terminated
{
    /**
     * Adds a restriction to the query based on the unit dates.
     *
     * @param   DatabaseQuery  $query     the query to modify
     * @param   string         $date      the date serving as an anchor for the generated restriction
     * @param   string         $interval  the restriction type
     *
     * @return void
     */
    public static function terminate(DatabaseQuery $query, string $date, string $interval): void
    {
        switch ($interval) {
            case 'term':
                $term = new Tables\Terms();
                $term->load(Terms::getCurrentID($date));
                $query->where(DB::qn('u.startDate') . ' >= :startDate')->bind(':startDate', $term->startDate)
                    ->where(DB::qn('u.endDate') . ' <= :endDate')->bind(':startDate', $term->endDate);
                break;
            case 'week':
                Dates::betweenColumns($query, $date, 'u.startDate', 'u.endDate');
                break;
            case 'day':
                $query->innerJoin(DB::qn('#__organizer_blocks', 'b'), DB::qc('b.id', 'i.blockID'))
                    ->where(DB::qn('b.date') . ' = :date')->bind(':date', $date);
                break;
        }
    }
}