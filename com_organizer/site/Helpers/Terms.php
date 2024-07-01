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
     * Gets the id of the term whose dates encompass the current date
     *
     * @param   string  $date  the reference date
     *
     * @return int the id of the term for the dates used on success, otherwise 0
     */
    public static function currentID(string $date = ''): int
    {
        $date  = ($date and strtotime($date)) ? date('Y-m-d', strtotime($date)) : date('Y-m-d');
        $query = DB::getQuery();
        $query->select('id')->from('#__organizer_terms');
        Dates::betweenColumns($query, $date, 'startDate', 'endDate');
        DB::setQuery($query);

        return DB::loadInt();
    }

    /**
     * Checks for the term end date for a given term id
     *
     * @param   int  $termID  the term's id
     *
     * @return string|null  string the end date of the term could be resolved, otherwise null
     */
    public static function endDate(int $termID): ?string
    {
        $table = new Tables\Terms();

        return $table->load($termID) ? $table->endDate : null;
    }

    /**
     * The ids of terms that have already expired. Used in various cleaning functions.
     * @return array
     */
    public static function expiredIDs(): array
    {
        $query = DB::getQuery();
        $query->select(DB::qn('id'))
            ->from(DB::qn('#__organizer_terms'))
            ->where(DB::qc('endDate', date('Y-m-d'), '<', true));
        DB::setQuery($query);

        return DB::loadIntColumn();
    }

    /**
     * Checks for the term entry in the database, creating it as necessary.
     *
     * @param   bool  $filter  if true only current and future terms will be displayed
     *
     * @return int[]  the term ids
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
     * Retrieves the ID of the term occurring immediately after the reference term.
     *
     * @param   int  $currentID  the id of the reference term
     *
     * @return int the id of the subsequent term if successful, otherwise 0
     */
    public static function nextID(int $currentID = 0): int
    {
        if (empty($currentID)) {
            $currentID = self::currentID();
        }

        $currentEndDate = self::endDate($currentID);
        $query          = DB::getQuery();
        $query->select('id')
            ->from('#__organizer_terms')
            ->where("startDate > '$currentEndDate'")
            ->order('startDate');
        DB::setQuery($query);

        return DB::loadInt();
    }

    /**
     * @inheritDoc
     *
     * @param   bool  $showDates  if true the start and end date will be displayed as part of the name
     * @param   bool  $filter     if true only current and future terms will be displayed
     */
    public static function options(bool $showDates = false, bool $filter = false): array
    {
        $tag     = Application::getTag();
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
     * @inheritDoc
     *
     * @param   bool  $filter
     */
    public static function resources(bool $filter = false): array
    {
        $query = DB::getQuery();
        $query->select('DISTINCT term.*')->from('#__organizer_terms AS term')->order('startDate');

        if ($view = Input::getView() and $view === 'Schedules') {
            $query->innerJoin('#__organizer_schedules AS s ON s.termID = term.id');
        }

        if ($filter) {
            $today = date('Y-m-d');
            $query->where("term.endDate > '$today'");
        }

        DB::setQuery($query);

        return DB::loadAssocList('id');
    }

    /**
     * Checks for the term start date for a given term id
     *
     * @param   int  $termID  the term's id
     *
     * @return string|null  string the end date of the term could be resolved, otherwise null
     */
    public static function startDate(int $termID): ?string
    {
        $table = new Tables\Terms();

        return $table->load($termID) ? $table->startDate : null;
    }
}
