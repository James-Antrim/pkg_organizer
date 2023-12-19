<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer\Fields;

use SimpleXMLElement;

/**
 * Adds the ability to set attribute values that will not later be overwritten by element parsing.
 */
trait Malleable
{
    public function setAttribute(string $attribute, string $value): void
    {
        if ($this->element instanceof SimpleXMLElement) {
            $attributes             = $this->element->attributes();
            $attributes->$attribute = $value;
        }
    }
}