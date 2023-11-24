<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Tables;

trait Incremented
{
    /**
     * INT(11) UNSIGNED NOT NULL AUTO_INCREMENT
     * @var int
     */
    public int $id;
}