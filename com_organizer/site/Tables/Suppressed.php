<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Tables;

/**
 * Resources which can be reached over a URL are addressable.
 */
trait Suppressed
{
    /**
     * A flag which displays whether associated resources should be suppressed from public display.
     * TINYINT(1) UNSIGNED NOT NULL
     * @var int
     */
    public $suppress;
}