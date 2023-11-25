<?php /** @noinspection PhpMissingFieldTypeInspection */

/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Tables;

/**
 * Adds the id property to tables.
 */
trait Incremented
{
    /**
     * Generally:
     *
     *
     * Frequent associatons will have a larger definition, rudimentary resources smaller.
     * Participants table is not explicilty UNSIGNED because of the reference to the #__users table.
     *
     * @var int
     */
    public $id;
}