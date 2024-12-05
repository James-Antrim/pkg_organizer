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

use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input};
use THM\Organizer\Tables;

/**
 * Provides general functions for term access checks, data retrieval and display.
 */
class Terms extends ResourceHelper implements Selectable
{
    use Numbered;

    /**
     * Gets the id of the term whose dates encompass the current date, defaults to the current date.
     *
     * @param   string  $date  the reference date
     *
     * @return int
     */
    public static function currentID(string $date = ''): int
    {
        $date  = ($date and strtotime($date)) ? date('Y-m-d', strtotime($date)) : date('Y-m-d');
        $query = DB::query();
        $query->select(DB::qn('id'))->from(DB::qn('#__organizer_terms'));
        DB::between($query, $date, 'startDate', 'endDate');
        DB::set($query);

        return DB::integer();
    }

    /**
     * Checks for the term end date for a given term id, defaults to current term id.
     *
     * @param   int  $termID  the term's id
     *
     * @return string|null
     */
    public static function endDate(int $termID = 0): ?string
    {
        $table  = new Tables\Terms();
        $termID = $termID ?: self::currentID();

        return $table->load($termID) ? $table->endDate : null;
    }

    /**
     * The ids of terms that have already expired. Used in various cleaning functions.
     * @return array
     */
    public static function expiredIDs(): array
    {
        $query = DB::query();
        $query->select(DB::qn('id'))
            ->from(DB::qn('#__organizer_terms'))
            ->where(DB::qc('endDate', date('Y-m-d'), '<', true));
        DB::set($query);

        return DB::integers();
    }

    /**
     * Gets the ids of term resources.
     *
     * @param   bool  $filter  if true only current and future terms will be displayed
     *
     * @return int[]
     */
    public static function getIDs(bool $filter = false): array
    {
        $ids = [];

        foreach (self::resources($filter) as $term) {
            $ids[] = (int) $term['id'];
        }

        return $ids;
    }

    /**
     * Retrieves the id of the term after the reference term, defaults to current term id.
     *
     * @param   int  $currentID  the id of the reference term
     *
     * @return int
     */
    public static function nextID(int $currentID = 0): int
    {
        $currentID = $currentID ?: self::currentID();

        $query = DB::query();
        $query->select(DB::qn('id'))
            ->from(DB::qn('#__organizer_terms'))
            ->where(DB::qc('startDate', self::endDate($currentID), '>', true))
            ->order(DB::qn('startDate'));
        DB::set($query);

        return DB::integer();
    }

    /**
     * @inheritDoc
     *
     * @param   bool  $showDates  if true the start and end date will be displayed as part of the name
     * @param   bool  $filter     if true only current and future terms will be displayed
     */
    public static function options(bool $showDates = false, bool $filter = false): array
    {
        $tag     = Application::tag();
        $options = [];

        foreach (Terms::resources($filter) as $term) {
            $name = $term["name_$tag"];

            if ($showDates) {
                $startDate = Dates::formatDate($term['startDate']);
                $endDate   = Dates::formatDate($term['endDate']);
                $name      .= " ($startDate - $endDate)";
            }

            $options[] = HTML::option($term['id'], $name);
        }

        return $options;
    }

    /**
     * Retrieves the id of the term before the reference term, defaults to current term id.
     *
     * @param   int  $currentID  the id of the reference term
     *
     * @return int
     */
    public static function previousID(int $currentID = 0): int
    {
        $currentID = $currentID ?: self::currentID();

        $query = DB::query();
        $query->select(DB::qn('id'))
            ->from(DB::qn('#__organizer_terms'))
            ->where(DB::qc('endDate', self::startDate($currentID), '<', true))
            ->order(DB::qn('endDate') . ' DESC');
        DB::set($query);

        return DB::integer();
    }

    /**
     * @inheritDoc
     *
     * @param   bool  $filter
     */
    public static function resources(bool $filter = false): array
    {
        $query = DB::query();
        $query->select('DISTINCT ' . DB::qn('term') . '.*')
            ->from(DB::qn('#__organizer_terms', 'term'))
            ->order(DB::qn('startDate'));

        if ($view = Input::getView() and $view === 'Schedules') {
            $query->innerJoin(DB::qn('#__organizer_schedules', 's'), DB::qc('s.termID', 'term.id'));
        }

        if ($filter) {
            $query->where(DB::qc('term.endDate', date('Y-m-d'), '>', true));
        }

        DB::set($query);

        return DB::arrays('id');
    }

    /**
     * Checks for the term start date for a given term id, defaults to current term id.
     *
     * @param   int  $termID  the term's id
     *
     * @return string|null
     */
    public static function startDate(int $termID = 0): ?string
    {
        $table  = new Tables\Terms();
        $termID = $termID ?: self::currentID();

        return $table->load($termID) ? $table->startDate : null;
    }
}
