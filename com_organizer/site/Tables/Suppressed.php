<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Tables;

/**
 * Adds a flag for resources for which individual resources can be explicitly censored from public viewership.
 */
trait Suppressed
{
    /**
     * A flag which displays whether associated resources should be suppressed from public display.
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
     * @var bool
     */
    public bool $suppress = false;
}