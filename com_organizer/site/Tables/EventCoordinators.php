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
class EventCoordinators extends Table
{
    /**
     * The id of the event entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public int $eventID;

    /**
     * The id of the person entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public int $personID;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_event_coordinators', 'id', $dbo);
    }
}
