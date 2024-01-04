<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2024 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer\Helpers;


use THM\Organizer\Adapters\HTML;

trait Pinned
{
    /**
     * Gets the resource's coordinates.
     *
     * @param   int  $resourceID  the id of the campus
     *
     * @return string the HTML for the location link
     */
    public static function getLocation(int $resourceID): string
    {
        $table = self::getTable();
        $table->load($resourceID);

        return empty($table->location) ? '' : $table->location;
    }

    /**
     * Returns a pin icon with a link for the location
     *
     * @param   int|string  $input  int the id of the campus, string the location coordinates
     *
     * @return string the html output of the pin
     */
    public static function getPin(int|string $input): string
    {
        $location = is_int($input) ? self::getLocation($input) : $input;

        if (!preg_match('/^-?[\d]?[\d].[\d]{6},-?[01]?[\d]{1,2}.[\d]{6}$/', $location)) {
            return '';
        }

        $icon = HTML::icon('fa fa-map-marker-alt');
        $url  = "https://www.google.de/maps/place/$location";
        return HTML::link($url, $icon, ['target' => '_blank']);
    }
}