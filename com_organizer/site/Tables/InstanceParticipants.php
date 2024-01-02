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
class InstanceParticipants extends Table
{
    /**
     * Whether the participant actually attended the course. Values: 0 - Unattended, 1 - Attended.
     * TINYINT(1) UNSIGNED DEFAULT 0
     * @var bool
     */
    public bool $attended = false;

    /**
     * INT(20) UNSIGNED NOT NULL AUTO_INCREMENT
     *
     * @var int
     */
    public int $id;

    /**
     * The id of the instance entry referenced.
     * INT(20) UNSIGNED NOT NULL
     * @var int
     */
    public int $instanceID;

    /**
     * The id of the participant entry referenced.
     * INT(11) NOT NULL
     * @var int
     */
    public int $participantID;

    /**
     * Whether the participant has registered to physically attend the instance. Values: 0 - No, 1 - Yes.
     * TINYINT(1) UNSIGNED DEFAULT 0
     * @var bool
     */
    public bool $registered = false;

    /**
     * The id of the room entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $roomID;

    /**
     * The identifier of the seat.
     * VARCHAR(60) NOT NULL DEFAULT ''
     * @var null|string
     */
    public null|string $seat;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_instance_participants', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        if (empty($this->roomID)) {
            $this->roomID = null;
        }

        if (empty($this->seat)) {
            $this->roomID = null;
        }

        return true;
    }
}
