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
class Bookings extends Table
{
    use Coded;

    /**
     * The id of the block entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public int $blockID;

    /**
     * The end time of the block.
     * TIME DEFAULT NULL
     * @var string|null
     */
    public string|null $endTime = null;

    /**
     * INT(20) UNSIGNED NOT NULL AUTO_INCREMENT
     *
     * @var int
     */
    public int $id;

    /**
     * The start time of the block.
     * TIME DEFAULT NULL
     * @var string|null
     */
    public string|null $startTime = null;

    /**
     * The id of the unit entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public int $unitID;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_bookings', 'id', $dbo);
    }
}
