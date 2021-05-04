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
 * Models the organizer_instance_participants table.
 */
class InstanceParticipants extends BaseTable
{
    /**
     * Whether or not the participant actually attended the course. Values: 0 - Unattended, 1 - Attended.
     * TINYINT(1) UNSIGNED DEFAULT 0
     *
     * @var bool
     */
    public $attended;

    /**
     * The id of the instance entry referenced.
     * INT(20) UNSIGNED NOT NULL
     *
     * @var int
     */
    public $instanceID;

    /**
     * The id of the participant entry referenced.
     * INT(11) NOT NULL
     *
     * @var int
     */
    public $participantID;

    /**
     * The id of the room entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     *
     * @var int
     */
    public $roomID;

    /**
     * The id of the room entry referenced.
     * VARCHAR(60) NOT NULL DEFAULT ''
     *
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
     * Set the table column names which are allowed to be null
     *
     * @return bool  true
     */
    public function check(): bool
    {
        if (empty($this->roomID)) {
            $this->roomID = null;
        }

        return true;
    }
}
