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

use Joomla\Database\{DatabaseDriver, DatabaseInterface};
use THM\Organizer\Adapters\Application;

/**
 * @inheritDoc
 */
class Rooms extends Table
{
    use Activated;
    use Aliased;
    use Coded;

    /**
     * The surface area of the room.
     * DOUBLE(6, 2) UNSIGNED NOT NULL DEFAULT 0.00
     * @var float
     */
    public float $area = 0.00;

    /**
     * The id of the building entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $buildingID;

    /**
     * The rooms effective occupancy for participants.
     * INT(4) UNSIGNED NOT NULL DEFAULT 0
     * @var int
     */
    public int $effCapacity = 0;

    /**
     * The id of the corresponding flooring entry
     * SMALLINT(3) UNSIGNED DEFAULT 1
     * @var int
     */
    public int $flooringID = 1;

    /**
     * The rooms maximum occupancy for participants.
     * INT(4) UNSIGNED DEFAULT NULL
     * @var int
     */
    public int $maxCapacity = 0;

    /**
     * The resource's name.
     * VARCHAR(150) NOT NULL
     * @var string
     */
    public string $name;

    /**
     * The id of the roomtype entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $roomtypeID;

    /**
     * A flag which displays whether the room is a virtual room.
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
     * @var int
     * @bool
     */
    public int $virtual = 0;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_rooms', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        $nullColumns = ['alias', 'buildingID'];
        foreach ($nullColumns as $nullColumn) {
            if (!$this->$nullColumn) {
                $this->$nullColumn = null;
            }
        }

        return true;
    }
}
