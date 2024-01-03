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
use THM\Organizer\Helpers\Monitors as Helper;

/**
 * @inheritDoc
 */
class Monitors extends Table
{
    /**
     * The file name of the content to be displayed.
     * VARCHAR(256) DEFAULT ''
     * @var string
     */
    public string $content = '';

    /**
     * The refresh interval (seconds) for content display.
     * INT(3) UNSIGNED NOT NULL DEFAULT 60
     * @var int
     */
    public int $contentRefresh;

    /**
     * A flag displaying for component or monitor specific settings.
     * INT(1) UNSIGNED NOT NULL DEFAULT 1
     * @var int
     * @see Helper for values
     */
    public int $display = Helper::CURRENT;

    /**
     * The interval (minutes) between display type switches.
     * INT(1) UNSIGNED NOT NULL DEFAULT 1
     * @var int
     */
    public int $interval = 1;

    /**
     * The ip address associated with the monitor.
     * VARCHAR(15) NOT NULL
     * @var string
     */
    public string $ip;

    /**
     * The id of the room entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $roomID;

    /**
     * The refresh interval (seconds) for schedule display.
     * INT(3) UNSIGNED NOT NULL DEFAULT 60
     * @var int
     */
    public int $scheduleRefresh;

    /**
     * The monitor settings source. Values: 0 - Monitor Specific, 1 - Component
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
     * @var int
     * @bool
     */
    public int $useDefaults = 0;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_monitors', 'id', $dbo);
    }
}
