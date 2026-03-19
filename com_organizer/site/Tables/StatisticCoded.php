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
 * Adds a field with a code from ITS department.
 */
trait StatisticCoded
{
    /**
     * A code from the ITS department.
     * VARCHAR(10) NOT NULL
     * @var string
     */
    public string $statisticCode;
}