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
class InstanceRooms extends Table
{
    use Modified;

    /**
     * The id of the instance persons entry referenced.
     * INT(20) UNSIGNED NOT NULL
     * @var int
     */
    public int $assocID;

    /**
     * INT(20) UNSIGNED NOT NULL AUTO_INCREMENT
     *
     * @var int
     */
    public int $id;

    /**
     * The id of the room entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public int $roomID;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::database();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_instance_rooms', 'id', $dbo);
    }
}
