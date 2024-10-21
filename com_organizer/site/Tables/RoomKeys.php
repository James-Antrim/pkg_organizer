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
class RoomKeys extends Table
{
    use Localized;

    /**
     * The cleaning group associated with the room key. Null is available as the referenced purposefully does not cascade on delete.
     * TINYINT(2) UNSIGNED  DEFAULT NULL
     * @var int|null
     */
    public int|null $cleaningID = null;

    /**
     * SMALLINT(3) UNSIGNED NOT NULL
     *
     * @var int
     */
    public int $id;

    /**
     * The actual room key.
     * VARCHAR(3) NOT NULL
     * @var string
     */
    public string $key;

    /**
     * The use group associated with the room key.
     * TINYINT(1) UNSIGNED  NOT NULL
     * @var int
     */
    public int $useID;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::database();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_roomkeys', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        if (empty($this->cleaningID)) {
            $this->cleaningID = null;
        }

        return true;
    }
}
