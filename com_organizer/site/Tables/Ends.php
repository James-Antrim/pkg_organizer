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
 * Adds a standard definition for the endDate column.
 */
trait Ends
{
    /**
     * The end date of the resource.
     * DATE NOT NULL
     * @var string
     */
    public string $endDate;
}