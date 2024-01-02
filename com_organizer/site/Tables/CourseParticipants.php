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
class CourseParticipants extends Table
{
    /**
     * Whether the participant actually attended the course. Values: 0 - Unattended, 1 - Attended.
     * TINYINT(1) UNSIGNED DEFAULT 0
     * @var bool
     */
    public bool $attended = false;

    /**
     * The id of the course entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public int $courseID;

    /**
     * INT(20) UNSIGNED NOT NULL AUTO_INCREMENT
     *
     * @var int
     */
    public int $id;

    /**
     * The participant's course payment status. Values: 0 - Unpaid, 1 - Paid.
     * TINYINT(1) UNSIGNED DEFAULT 0
     * @var bool
     */
    public bool $paid = false;

    /**
     * The date and time of the last participant initiated change.
     * DATETIME DEFAULT NULL
     * @var null|string
     */
    public null|string $participantDate = null;

    /**
     * The id of the participant entry referenced.
     * INT(11) NOT NULL
     * @var int
     */
    public int $participantID;

    /**
     * The participant's course status. Values: 0 - Pending, 1 - Accepted.
     * TINYINT(1) UNSIGNED DEFAULT 0
     * @var int
     */
    public int $status = 0;

    /**
     * The date and time of the last change.
     * DATETIME DEFAULT NULL
     * @var null|string
     */
    public null|string $statusDate = null;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_course_participants', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        if (!$this->participantDate) {
            $this->participantDate = null;
        }

        if (!$this->statusDate) {
            $this->statusDate = null;
        }

        return true;
    }
}
