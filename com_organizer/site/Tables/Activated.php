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
 * Adds a flag denoting resource activation status.
 */
trait Activated
{
    /**
     * A flag which displays whether the resource is currently active.
     * TINYINT(1) UNSIGNED NOT NULL
     * @var bool
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $active;
}