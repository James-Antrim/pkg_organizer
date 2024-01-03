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
class Schedules extends Table
{
    /**
     * The date of the schedule's creation.
     * DATE DEFAULT NULL
     * @var string|null
     */
    public string|null $creationDate;

    /**
     * The time of the schedule's creation.
     * TIME DEFAULT NULL
     * @var string|null
     */
    public string|null $creationTime;

    /**
     * The id of the organization entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public int $organizationID;

    /**
     * A collection of instance objects modeled by a JSON string.
     * MEDIUMTEXT NOT NULL
     * @var string
     */
    public string $schedule;

    /**
     * The id of the term entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public int $termID;

    /**
     * The id of the user entry referenced.
     * INT(11) DEFAULT NULL
     * @var int|null
     */
    public int|null $userID;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_schedules', 'id', $dbo);
    }
}
