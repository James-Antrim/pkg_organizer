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
 * Provides fields for resources with rudimentary logging with a check to prevent setting the 0 date as a default.
 */
trait Modified
{
    /**
     * The resource's delta status. Possible values: '', 'new,' 'removed'.
     * VARCHAR(10) NOT NULL DEFAULT ''
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $delta;

    /**
     * The timestamp at which the schedule was generated which modified this entry.
     * TIMESTAMP DEFAULT CURRENT_TIMESTAMP
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $modified;

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        if ($this->modified === '0000-00-00 00:00:00') {
            $this->modified = null;
        }

        return true;
    }
}