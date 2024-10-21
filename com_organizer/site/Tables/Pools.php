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
class Pools extends Table
{
    use Aliased;
    use LSFImported;

    /**
     * The resource's German abbreviation.
     * VARCHAR(25) NOT NULL DEFAULT ''
     * @var string
     */
    public string $abbreviation_de = '';

    /**
     * The resource's English abbreviation.
     * VARCHAR(25) NOT NULL DEFAULT ''
     * @var string
     */
    public string $abbreviation_en = '';

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
     * The id of the field entry referenced. Independent of FK cascading, this can legitimately not reference a field.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $fieldID = null;

    /**
     * The resource's German name.
     * VARCHAR(200) DEFAULT NULL
     * @var string|null
     */
    public string|null $fullName_de = '';

    /**
     * The resource's English name.
     * VARCHAR(200) DEFAULT NULL
     * @var string|null
     */
    public string|null $fullName_en = '';

    /**
     * The id of the group entry referenced. Independent of FK cascading, this can legitimately not reference a group.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $groupID = null;

    /**
     * The maximum credit points required to be achieved in subjects of this pool.
     * INT(3) UNSIGNED DEFAULT 0
     * @var int
     */
    public int $maxCrP = 0;

    /**
     * The minimum credit points required to be achieved in subjects of this pool.
     * INT(3) UNSIGNED DEFAULT 0
     * @var int
     */
    public int $minCrP = 0;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::database();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_pools', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        // These can be blank, but non-empty values should be unique.
        $nullColumns = ['alias', 'groupID', 'fieldID', 'lsfID'];
        foreach ($nullColumns as $nullColumn) {
            if (!$this->$nullColumn) {
                $this->$nullColumn = null;
            }
        }

        return true;
    }
}
