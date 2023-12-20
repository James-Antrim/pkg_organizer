<?php /** @noinspection PhpMissingFieldTypeInspection */

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
trait Aliased
{
    /**
     * The alias used to reference the resource in a URL
     * VARCHAR(255) DEFAULT NULL
     * @var null|string
     */
    public null|string $alias;

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        if (empty($this->alias)) {
            $this->alias = null;
        }

        return true;
    }
}