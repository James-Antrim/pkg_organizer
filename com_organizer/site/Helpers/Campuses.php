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

use Organizer\Adapters\Database;
use Organizer\Tables;

/**
 * Provides general functions for campus access checks, data retrieval and display.
 */
class Campuses extends ResourceHelper implements Selectable
{
    /**
     * Retrieves the default grid id for the given campus
     *
     * @param int $campusID the id of the campus
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
     * Creates a link to the campus' location
     *
     * @param int $campusID the id of the campus
     *
     * @return string the HTML for the location link
     */
    public static function getLocation(int $campusID): string
    {
        $table = new Tables\Campuses();
        $table->load($campusID);

        return empty($table->location) ? '' : $table->location;
    }

    /**
     * Gets the qualified campus name
     *
     * @param int $resourceID the campus' id
     *
     * @return string the name if the campus could be resolved, otherwise empty
     */
    public static function getName(int $resourceID = 0): string
    {
        if (empty($resourceID)) {
            return '';
        }

        $tag   = Languages::getTag();
        $query = Database::getQuery(true);
        $query->select("c1.name_$tag as name, c2.name_$tag as parentName")
            ->from('#__organizer_campuses AS c1')
            ->leftJoin('#__organizer_campuses AS c2 ON c2.id = c1.parentID')
            ->where("c1.id = $resourceID");
        Database::setQuery($query);

        if (!$names = Database::loadAssoc()) {
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

            $options[$name] = HTML::_('select.option', $campus['id'], $name);
        }

        ksort($options);

        return $options;
    }

    /**
     * @inheritDoc
     */
    public static function getResources(): array
    {
        $tag   = Languages::getTag();
        $query = Database::getQuery();
        $query->select("c1.*, c1.name_$tag AS name")
            ->from('#__organizer_campuses AS c1')
            ->select("c2.name_$tag as parentName")
            ->leftJoin('#__organizer_campuses AS c2 ON c2.id = c1.parentID')
            ->order('parentName, name');

        $selectedIDs = Input::getSelectedIDs();
        $view        = Input::getView();

        // Only parents
        if (strtolower($view) === 'campus_edit') {
            $query->where("c1.parentID IS NULL");

            // Not self
            if (count($selectedIDs)) {
                $query->where("c1.id != $selectedIDs[0]");
            }
        }

        Database::setQuery($query);

        return Database::loadAssocList('id');
    }

    /**
     * Returns a pin icon with a link for the location
     *
     * @param int|string $input int the id of the campus, string the location coordinates
     *
     * @return string the html output of the pin
     */
    public static function getPin($input): string
    {
        $isID     = is_numeric($input);
        $location = $isID ? self::getLocation($input) : $input;

        if (!preg_match('/^-?[\d]?[\d].[\d]{6},-?[01]?[\d]{1,2}.[\d]{6}$/', $location)) {
            return '';
        }

        $pin = '<a target="_blank" href="https://www.google.de/maps/place/' . $location . '">';
        $pin .= '<span class="icon-location"></span></a>';

        return $pin;
    }
}
