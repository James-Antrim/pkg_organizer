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
class Campuses extends Table
{
    use Activated;
    use Aliased;
    use Localized;

    /**
     * The physical address of the resource.
     * VARCHAR(255) NOT NULL
     * @var string
     */
    public string $address;

    /**
     * The city in which the resource is located.
     * VARCHAR(60) NOT NULL
     * @var string
     */
    public string $city;

    /**
     * The id of the grid entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $gridID = null;

    /**
     * A flag displaying if the campus is equatable with a city for internal purposes.
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
     * @var int
     * @bool
     */
    public int $isCity = 0;

    /**
     * The GPS coordinates of the resource.
     * VARCHAR(20) NOT NULL
     * @var string
     */
    public string $location;

    /**
     * The id of the campus entry referenced as parent.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $parentID = null;

    /**
     * The ZIP code of the resource.
     * VARCHAR(60) NOT NULL
     * @var string
     */
    public string $zipCode;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_campuses', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        if (empty($this->alias)) {
            $this->alias = null;
        }

        if (empty($this->gridID)) {
            $this->gridID = null;
        }

        if (empty($this->parentID)) {
            $this->parentID = null;
        }

        $this->location = str_replace(' ', '', $this->location);

        return true;
    }
}
