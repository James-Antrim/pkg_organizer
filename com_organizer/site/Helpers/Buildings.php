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

use THM\Organizer\Adapters\Database;
use THM\Organizer\Tables;

/**
 * Class provides general functions for retrieving building data.
 */
class Buildings extends ResourceHelper implements Selectable
{
    use Filtered;

    /**
     * Checks for the building entry in the database, creating it as necessary. Adds the id to the building entry in the
     * schedule.
     *
     * @param string $name the building name
     *
     * @return int|null  int the id if the room could be resolved/added, otherwise null
     */
    public static function getID(string $name): ?int
    {
        $table = new Tables\Buildings();
        $data  = ['name' => $name];

        if ($table->load($data)) {
            return $table->id;
        }

        return $table->save($data) ? $table->id : null;
    }

    /**
     * @inheritDoc
     */
    public static function getOptions(): array
    {
        // Array values allows easier manipulation of entries for buildings of the same name on different campuses.
        if (!$buildings = array_values(self::getResources())) {
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
                $options[]    = HTML::_('select.option', $thisBuilding['id'], $buildingName);
                continue;
            }

            // The campus name is relevant to unique identification
            $nextBuilding = $buildings[$index + 1];

            $thisCampusID = empty($thisBuilding['parentID']) ? $thisBuilding['campusID'] : $thisBuilding['parentID'];
            $nextCampusID = empty($nextBuilding['parentID']) ? $nextBuilding['campusID'] : $nextBuilding['parentID'];

            $thisBuilding['campusName'] = Campuses::getName($thisCampusID);
            $nextBuilding['campusName'] = Campuses::getName($nextCampusID);

            if ($thisBuilding['campusName'] < $nextBuilding['campusName']) {
                $buildingID   = $thisBuilding['id'];
                $buildingName .= " ({$thisBuilding['campusName']})";

                $buildings[$index + 1] = $nextBuilding;
            } else {
                $buildingID   = $nextBuilding['id'];
                $buildingName .= " ({$nextBuilding['campusName']})";

                $buildings[$index + 1] = $thisBuilding;
            }

            $options[] = HTML::_('select.option', $buildingID, $buildingName);
        }

        return $options;
    }

    /**
     * @inheritDoc
     */
    public static function getResources(): array
    {
        $query = Database::getQuery(true);
        $query->select('DISTINCT b.*, c.parentID')
            ->from('#__organizer_buildings AS b')
            ->leftJoin('#__organizer_campuses AS c ON c.id = b.campusID')
            ->order('name');
        self::addCampusFilter($query, 'b');
        Database::setQuery($query);

        return Database::loadAssocList('id');
    }
}
