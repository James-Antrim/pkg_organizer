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
use Joomla\Database\ParameterType;
use THM\Organizer\Tables;

/**
 * Provides general functions for campus access checks, data retrieval and display.
 */
class Campuses extends ResourceHelper implements Selectable
{
    use Active;
    use Pinned;

    /**
     * Retrieves the default grid id for the given campus
     *
     * @param   int  $campusID  the id of the campus
     *
     * @return int the id of the associated grid
     */
    public static function getGridID(int $campusID): int
    {
        $table = new Tables\Campuses();
        if (!$table->load($campusID)) {
            return 0;
        }

        if ($gridID = $table->gridID) {
            return $gridID;
        }

        if ($parentID = $table->parentID) {
            return self::getGridID($parentID);
        }

        return 0;
    }

    /**
     * Gets the qualified campus name
     *
     * @param   int  $resourceID  the campus' id
     *
     * @return string the name if the campus could be resolved, otherwise empty
     */
    public static function getName(int $resourceID = 0): string
    {
        if (empty($resourceID)) {
            return '';
        }

        $tag   = Application::getTag();
        $query = DB::getQuery();
        $query->select(DB::qn(["c1.name_$tag", "c2.name_$tag"], ['name', 'parentName']))
            ->from(DB::qn('#__organizer_campuses', 'c1'))
            ->leftJoin(DB::qn('#__organizer_campuses', 'c2'), DB::qc('c2.id', 'c1.parentID'))
            ->where(DB::qn('c1.id') . ' = :resourceID')->bind(':resourceID', $resourceID, ParameterType::INTEGER);
        DB::setQuery($query);

        if (!$names = DB::loadAssoc()) {
            return '';
        }

        return empty($names['parentName']) ? $names['name'] : "{$names['parentName']} / {$names['name']}";
    }

    /**
     * @inheritDoc
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::getResources() as $campus) {
            $name = empty($campus['parentName']) ? $campus['name'] : "{$campus['parentName']} / {$campus['name']}";

            $options[$name] = HTML::option($campus['id'], $name);
        }

        ksort($options);

        return $options;
    }

    /**
     * @inheritDoc
     */
    public static function getResources(): array
    {
        $tag   = Application::getTag();
        $query = DB::getQuery();
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

        DB::setQuery($query);

        return DB::loadAssocList('id');
    }
}
