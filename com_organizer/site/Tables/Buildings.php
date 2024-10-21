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
class Buildings extends Table
{
    use Activated;

    /**
     * The physical address of the resource.
     * VARCHAR(255) NOT NULL
     * @var string
     */
    public string $address;

    /**
     * The id of the campus entry referenced. Null is available as the referenced purposefully does not cascade on delete.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $campusID = null;

    /**
     * The GPS coordinates of the resource.
     * VARCHAR(20) NOT NULL
     * @var string
     */
    public string $location;

    /**
     * The resource's name.
     * VARCHAR(60) NOT NULL
     * @var string
     */
    public string $name;

    /**
     * The property type. Values: 0 - New/Unknown | 1 - Owned | 2 - Leased/Rented
     * INT(1) UNSIGNED  NOT NULL DEFAULT 0
     * @var int
     */
    public int $propertyType = 0;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::database();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_buildings', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        if (!$this->campusID) {
            $this->campusID = null;
        }

        $this->location = str_replace(' ', '', $this->location);

        return true;
    }
}
