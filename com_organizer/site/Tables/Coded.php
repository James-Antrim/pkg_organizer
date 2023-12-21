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
 * Adds a field with an accepted short form code, predominantly from Untis scheduling software.
 */
trait Coded
{
    /**
     * An abbreviated nomenclature for the resource. Currently corresponding to the identifier in Untis scheduling
     * software except units which are also supplemented locally.
     * Generally: VARCHAR(60) NOT NULL, but also DEFAULT NULL
     * @var null|string
     */
    public null|string $code;
}