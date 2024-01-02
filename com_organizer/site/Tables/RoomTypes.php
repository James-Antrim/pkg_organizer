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
class RoomTypes extends Table
{
    use Localized;
    use Suppressed;

    /**
     * The resource's German description.
     * TEXT
     * @var string
     */
    public string $description_de = '';

    /**
     * The resource's English description.
     * TEXT
     * @var string
     */
    public string $description_en = '';

    /**
     * The maximum occupancy for rooms of this type.
     * INT(4) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $capacity;

    /**
     * The id of the normed use code with which the type is associated.
     * SMALLINT(4) UNSIGNED NOT NULL
     * @var int
     */
    public int $usecode;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_roomtypes', 'id', $dbo);
    }
}
