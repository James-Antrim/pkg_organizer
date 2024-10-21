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
class Blocks extends Table
{
    /**
     * The date of the block.
     * DATE DEFAULT NULL
     * @var string
     */
    public string $date;

    /**
     * The numerical day of the week of the block.
     * TINYINT(1) UNSIGNED NOT NULL
     * @var int
     */
    public int $dow;

    /**
     * The end time of the block.
     * TIME NOT NULL
     * @var string
     */
    public string $endTime;

    /**
     * The start time of the block.
     * TIME NOT NULL
     * @var string
     */
    public string $startTime;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::database();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_blocks', 'id', $dbo);
    }
}
