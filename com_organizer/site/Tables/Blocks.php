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
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $date;

    /**
     * The numerical day of the week of the block.
     * TINYINT(1) UNSIGNED NOT NULL
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $dow;

    /**
     * The end time of the block.
     * TIME DEFAULT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $endTime;

    /**
     * The start time of the block.
     * TIME DEFAULT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $startTime;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_blocks', 'id', $dbo);
    }
}
