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
 * Models the organizer_rooms table.
 */
class Rooms extends BaseTable
{
    use Activated;
    use Aliased;
    use Coded;

    /**
     * The surface area of the room.
     * DOUBLE(6, 2) UNSIGNED NOT NULL DEFAULT 0.00
     * @var float
     */
    public $area;

    /**
     * The id of the building entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int
     */
    public $buildingID;

    /**
     * The rooms effective occupancy for participants.
     * INT(4) UNSIGNED DEFAULT NULL
     * @var string
     */
    public $effCapacity;

    /**
     * The id of the corresponding flooring entry
     * SMALLINT(3) UNSIGNED
     * @var int
     */
    public $flooringID;

    /**
     * The rooms maximum occupancy for participants.
     * INT(4) UNSIGNED DEFAULT NULL
     * @var string
     */
    public $maxCapacity;

    /**
     * The resource's name.
     * VARCHAR(150) NOT NULL
     * @var string
     */
    public $name;

    /**
     * The id of the roomtype entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int
     */
    public $roomtypeID;

    /**
     * A flag which displays whether the room is a virtual room.
     * TINYINT(1) UNSIGNED NOT NULL
     * @var string
     */
    public $virtual;

    /**
     * Declares the associated table.
     */
    public function __construct()
    {
        parent::__construct('#__organizer_rooms');
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        $nullColumns = ['alias', 'buildingID'];
        foreach ($nullColumns as $nullColumn) {
            if (!strlen($this->$nullColumn)) {
                $this->$nullColumn = null;
            }
        }

        return true;
    }
}
