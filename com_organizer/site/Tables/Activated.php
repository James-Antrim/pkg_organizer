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
 * Resources which can be reached over a URL are addressable.
 */
trait Activated
{
    /**
     * A flag which displays whether the resource is currently active.
     * TINYINT(1) UNSIGNED NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $active;
}