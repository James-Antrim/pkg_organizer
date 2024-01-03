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
 * Adds a flag denoting resource relevance status.
 */
trait Relevant
{
    /**
     * A flag which displays whether associated rooms should appear in exported media.
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 1
     * @var int
     * @bool
     */
    public int $relevant = 1;
}