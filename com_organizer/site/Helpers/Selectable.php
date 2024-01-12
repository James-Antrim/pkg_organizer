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

/**
 * Ensures that helpers that reference selectable items offer the getOptions function.
 */
interface Selectable
{
    const ALL = '', NONE = -1;

    /**
     * Retrieves the selectable options for the resource.
     * @return array the available options
     */
    public static function options(): array;

    /**
     * Retrieves resource items.
     * @return array the available resources
     */
    public static function resources(): array;
}
