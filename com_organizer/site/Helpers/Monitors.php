<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

/**
 * Provides functions for XML instance validation and modeling.
 */
class Monitors
{
    public const UPCOMING = 0, CURRENT = 1, MIXED = 2, CONTENT = 3;

    public const LAYOUTS = [self::CONTENT, self::CURRENT, self::MIXED, self::UPCOMING];
}
