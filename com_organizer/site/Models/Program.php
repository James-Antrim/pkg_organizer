<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

/**
 * Class which manages stored (degree) program data.
 */
class Program extends EditModel
{
    /**
     * The resource's table class.
     * @var string
     */
    protected string $tableClass = 'Programs';
}
