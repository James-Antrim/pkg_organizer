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
 * Adds standard definitions for localized naming columns.
 */
trait Localized
{
    /**
     * VARCHAR(150) NOT NULL
     * @var string
     */
    public string $name_de;

    /**
     * VARCHAR(150) NOT NULL
     * @var string
     */
    public string $name_en;
}