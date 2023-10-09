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

use Joomla\CMS\Uri\Uri;

/**
 * Class provides generalized functions useful for several component files.
 */
class Routing
{
    /**
     * Builds a the base url for redirection
     * @return string the root url to redirect to
     */
    public static function getRedirectBase(): string
    {
        $base = Uri::base();

        if (OrganizerHelper::getApplication()->isClient('administrator')) {
            return "$base?option=com_organizer";
        }

        // If the menu is plausible redirect
        if ($menuID = Input::getItemid() and !OrganizerHelper::getApplication()->getMenu()->getItem($menuID)->home) {
            return $base . OrganizerHelper::getApplication()->getMenu()->getItem($menuID)->route . '?';
        }

        return "$base?option=com_organizer";
    }

    /**
     * Generates a link to a controller function.
     *
     * @param string $task the controller and function to be accessed
     * @param int    $id   the optional id of the resource to be displayed in the view
     *
     * @return string the task url
     */
    public static function getTaskURL(string $task, int $id = 0): string
    {
        $url = Uri::base() . "?option=com_organizer&task=$task";

        if ($id) {
            $url .= "&id=$id";
        }

        return $url;
    }

    /**
     * Generates a link to a view.
     *
     * @param string $view the view to be accessed
     * @param int    $id   the optional id of the resource to be displayed in the view
     *
     * @return string the view url
     */
    public static function getViewURL(string $view, int $id = 0): string
    {
        $url = Uri::base() . "?option=com_organizer&view=$view";

        if ($id) {
            $url .= "&id=$id";
        }

        return $url;
    }
}
