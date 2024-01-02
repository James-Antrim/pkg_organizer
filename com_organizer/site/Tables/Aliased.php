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
 * Adds rudimentary alias field and a common check routine for tables for whom only this field is nullable.
 */
trait Aliased
{
    /**
     * The alias used to reference the resource in a URL
     * VARCHAR(255) DEFAULT NULL
     * @var null|string
     */
    public null|string $alias = null;

    /**
     * @inheritDoc
     * Stand-alone check function for classes whose sole nullable column is alias.
     */
    public function check(): bool
    {
        if (empty($this->alias)) {
            $this->alias = null;
        }

        return true;
    }
}