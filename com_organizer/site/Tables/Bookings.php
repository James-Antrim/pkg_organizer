<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Tables;

/**
 * Models the organizer_instances table.
 */
class Bookings extends BaseTable
{
    use Coded;

    /**
     * The id of the block entry referenced.
     * INT(11) UNSIGNED NOT NULL
     *
     * @var int
     */
    public $blockID;

    /**
     * The end time of the block.
     * TIME DEFAULT NULL
     *
     * @var string
     */
    public $endTime;

    /**
     * The start time of the block.
     * TIME DEFAULT NULL
     *
     * @var string
     */
    public $startTime;

    /**
     * The id of the unit entry referenced.
     * INT(11) UNSIGNED NOT NULL
     *
     * @var int
     */
    public $unitID;

    /**
     * Declares the associated table.
     */
    public function __construct()
    {
        parent::__construct('#__organizer_bookings');
    }
}
