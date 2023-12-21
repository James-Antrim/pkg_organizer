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
    /**
     * The cleaning group associated with the room key.
     * TINYINT(2) UNSIGNED  DEFAULT NULL
     * @var int|null
     */
    public int|null $cleaningID;

    /**
     * The actual room key.
     * VARCHAR(3) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $key;

    /**
     * The room key's German name.
     * VARCHAR(150) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $name_de;

    /**
     * The room key's English name.
     * VARCHAR(150) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $name_en;

    /**
     * The use group associated with the room key.
     * TINYINT(1) UNSIGNED  NOT NULL
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $useID;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

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
