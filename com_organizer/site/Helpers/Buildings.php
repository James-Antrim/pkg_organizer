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

use THM\Organizer\Adapters\{Database as DB, HTML, Input};
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
use THM\Organizer\Tables\Buildings as Table;

/**
 * Class provides general functions for retrieving building data.
 */
class Buildings extends ResourceHelper implements Filterable, Selectable
{
    use Active;
    use Pinned;

    public const OWNED = 1, RENTED = 2, USED = 3;

    /**
     * @inheritDoc
     */
    public static function filterBy(DatabaseQuery $query, string $alias, int $resourceID): void
    {
        if ($resourceID === self::UNSELECTED) {
            return;
        }

        $tableID   = DB::qn('buildingAlias.id');
        $condition = DB::qc('buildingAlias.id', "$alias.buildingID");
        $table     = DB::qn("#__organizer_buildings", 'buildingAlias');

        if ($resourceID === self::NONE) {
            $query->leftJoin($table, $condition)->where("$tableID IS NULL");
            return;
        }

        $query->innerJoin($table, $condition)
            ->where("$tableID = :buildingID")
            ->bind(':buildingID', $resourceID, ParameterType::INTEGER);
    }

    /**
     * @inheritDoc
     */
    public static function options(): array
    {
        // Array values allows easier manipulation of entries for buildings of the same name on different campuses.
        if (!$buildings = array_values(self::resources())) {
            return $buildings;
        }

        $options = [];
        for ($index = 0; $index < count($buildings); $index++) {
            $thisBuilding = $buildings[$index];
            $buildingName = $thisBuilding['name'];

            $listEnd          = empty($buildings[$index + 1]);
            $standardHandling = ($listEnd or $thisBuilding['name'] != $buildings[$index + 1]['name']);

            if ($standardHandling) {
                $buildingName .= empty($thisBuilding['campusName']) ? '' : " ({$thisBuilding['campusName']})";
                $options[]    = HTML::option($thisBuilding['id'], $buildingName);
                continue;
            }

            // The campus name is relevant to unique identification
            $nextBuilding = $buildings[$index + 1];

            $thisCampusID = empty($thisBuilding['parentID']) ? $thisBuilding['campusID'] : $thisBuilding['parentID'];
            $nextCampusID = empty($nextBuilding['parentID']) ? $nextBuilding['campusID'] : $nextBuilding['parentID'];

            $thisBuilding['campusName'] = Campuses::name($thisCampusID);
            $nextBuilding['campusName'] = Campuses::name($nextCampusID);

            if ($thisBuilding['campusName'] < $nextBuilding['campusName']) {
                $buildingID   = $thisBuilding['id'];
                $buildingName .= " ({$thisBuilding['campusName']})";

                $buildings[$index + 1] = $nextBuilding;
            }
            else {
                $buildingID   = $nextBuilding['id'];
                $buildingName .= " ({$nextBuilding['campusName']})";

                $buildings[$index + 1] = $thisBuilding;
            }

            $options[] = HTML::option($buildingID, $buildingName);
        }

        return $options;
    }

    /**
     * Checks for the building name in the database, creating an entry for it as necessary.
     *
     * @param   string  $name  the building name
     *
     * @return int
     */
    public static function resolveID(string $name): int
    {
        $table = new Table();
        $data  = ['name' => $name];

        if ($table->load($data)) {
            return $table->id;
        }

        return $table->save($data) ? $table->id : 0;
    }

    /**
     * @inheritDoc
     */
    public static function resources(): array
    {
        $query = DB::query();
        $query->select(['DISTINCT ' . DB::qn('b') . '.*', DB::qn('c.parentID')])
            ->from(DB::qn('#__organizer_buildings', 'b'))
            ->leftJoin(DB::qn('#__organizer_campuses', 'c'), DB::qc('c.id', 'b.campusID'))
            ->order(DB::qn('name'));

        Campuses::filterBy($query, 'b', Input::getInt('campusID'));

        DB::set($query);

        return DB::arrays('id');
    }
}
