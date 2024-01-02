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
class RoomEquipment extends Table
{
    /**
     * The description of the specific equipment referenced: make, model, color, ...
     * VARCHAR(255) NOT NULL DEFAULT ''
     * @var string
     */
    public string $description = '';

    /**
     * The id of the equipment entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $equipmentID;

    /**
     * The id of the room entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $roomID;

    /**
     * The quantity of the referenced equipment in the room.
     * INT(4) UNSIGNED DEFAULT 1
     * @var int
     */
    public int $quantity = 1;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_room_equipment', 'id', $dbo);
    }
}
