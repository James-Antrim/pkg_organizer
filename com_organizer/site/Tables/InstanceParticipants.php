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
 * Models the organizer_instance_participants table.
 */
class InstanceParticipants extends BaseTable
{
    /**
     * Whether the participant actually attended the course. Values: 0 - Unattended, 1 - Attended.
     * TINYINT(1) UNSIGNED DEFAULT 0
     * @var bool
     */
    public $attended;

    /**
     * The id of the instance entry referenced.
     * INT(20) UNSIGNED NOT NULL
     * @var int
     */
    public $instanceID;

    /**
     * The id of the participant entry referenced.
     * INT(11) NOT NULL
     * @var int
     */
    public $participantID;

    /**
     * Whether the participant has registered to physically attend the instance. Values: 0 - No, 1 - Yes.
     * TINYINT(1) UNSIGNED DEFAULT 0
     * @var bool
     */
    public $registered;

    /**
     * The id of the room entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int
     */
    public $roomID;

    /**
     * The id of the room entry referenced.
     * VARCHAR(60) NOT NULL DEFAULT ''
     * @var string
     */
    public $seat;

    /**
     * Declares the associated table.
     */
    public function __construct()
    {
        parent::__construct('#__organizer_instance_participants');
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        if (empty($this->roomID)) {
            $this->roomID = null;
        }

        return true;
    }
}
