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

use Joomla\Database\{DatabaseQuery, ParameterType};
use Joomla\Utilities\ArrayHelper;
use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input};
use THM\Organizer\Tables;

/**
 * Provides general functions for campus access checks, data retrieval and display.
 */
class Campuses extends ResourceHelper implements Filterable, Selectable
{
    use Active;
    use Pinned;

    /**
     * Retrieves the ids of buildings associated with this campus.
     *
     * @param   int  $campusID  the id of the campus
     *
     * @return int[]
     */
    public static function buildings(int $campusID): array
    {
        $campus = self::getTable();

        if (!$campus->load($campusID)) {
            return [];
        }

        $campusIDs   = self::children($campusID);
        $campusIDs[] = $campusID;
        $campusIDs   = array_filter(ArrayHelper::toInteger($campusIDs));

        $query = DB::query();
        $query->select('DISTINCT ' . DB::qn('id'))
            ->from(DB::qn('#__organizer_buildings'))
            ->whereIN(DB::qn('campusID'), $campusIDs);

        DB::set($query);

        return DB::integers();
    }

    /**
     * Retrieves the ids of subordinate campuses.
     *
     * @param   int  $parentID  the id of the superordinate campus
     *
     * @return int[]
     */
    public static function children(int $parentID): array
    {
        $query = DB::query();
        $query->select(DB::qn('id'))->from(DB::qn('#__organizer_campuses'))->where(DB::qc('parentID', $parentID));
        DB::set($query);

        return DB::integers();
    }

    /** @inheritDoc */
    public static function filterBy(DatabaseQuery $query, string $alias, array $resourceIDs): void
    {
        if (!$resourceIDs) {
            return;
        }

        $tableID   = DB::qn('campusAlias.id');
        $condition = DB::qc('campusAlias.id', "$alias.campusID");
        $table     = DB::qn('#__organizer_campuses', 'campusAlias');

        if (in_array(self::NONE, $resourceIDs)) {
            $query->leftJoin($table, $condition)->where("$tableID IS NULL");
            return;
        }

        $parentID    = DB::qn('campusAlias.parentID');
        $resourceIDs = implode(', ', $resourceIDs);
        $query->innerJoin($table, $condition)->where("($tableID IN ($resourceIDs) OR $parentID  IN ($resourceIDs))");
    }

    /**
     * Retrieves the default grid id for the given campus
     *
     * @param   int  $campusID  the id of the campus
     *
     * @return int
     */
    public static function gridID(int $campusID): int
    {
        $table = new Tables\Campuses();
        if (!$table->load($campusID)) {
            return 0;
        }

        if ($gridID = $table->gridID) {
            return $gridID;
        }

        if ($parentID = $table->parentID) {
            return self::gridID($parentID);
        }

        return 0;
    }

    /**
     * Gets the qualified campus name
     *
     * @param   int  $resourceID  the campus' id
     *
     * @return string
     */
    public static function name(int $resourceID = 0): string
    {
        if (empty($resourceID)) {
            return '';
        }

        $tag   = Application::tag();
        $query = DB::query();
        $query->select(DB::qn(["c1.name_$tag", "c2.name_$tag"], ['name', 'parentName']))
            ->from(DB::qn('#__organizer_campuses', 'c1'))
            ->leftJoin(DB::qn('#__organizer_campuses', 'c2'), DB::qc('c2.id', 'c1.parentID'))
            ->where(DB::qn('c1.id') . ' = :resourceID')->bind(':resourceID', $resourceID, ParameterType::INTEGER);
        DB::set($query);

        if (!$names = DB::array()) {
            return '';
        }

        return empty($names['parentName']) ? $names['name'] : "{$names['parentName']} / {$names['name']}";
    }

    /** @inheritDoc */
    public static function options(): array
    {
        $options = [];
        foreach (self::resources() as $campus) {
            $name = empty($campus['parentName']) ? $campus['name'] : "{$campus['parentName']} / {$campus['name']}";

            $options[$name] = HTML::option($campus['id'], $name);
        }

        ksort($options);

        return $options;
    }

    /** @inheritDoc */
    public static function resources(): array
    {
        $tag   = Application::tag();
        $query = DB::query();
        $query->select([DB::qn('c1') . '.*', DB::qn("c1.name_$tag", 'name'), DB::qn("c2.name_$tag", 'parentName')])
            ->from(DB::qn('#__organizer_campuses', 'c1'))
            ->leftJoin(DB::qn('#__organizer_campuses', 'c2'), DB::qc('c2.id', 'c1.parentID'))
            ->order(DB::qn(['parentName', 'name']));

        // Only parents
        if (strtolower(Input::getView()) === 'campus') {
            $query->where(DB::qn('c1.parentID') . ' IS NULL');

            // Not self
            if ($campusID = Input::getID()) {
                $query->where(DB::qn('c1.id') . ' != :campusID')->bind(':campusID', $campusID, ParameterType::INTEGER);
            }
        }

        DB::set($query);

        return DB::arrays('id');
    }
}
