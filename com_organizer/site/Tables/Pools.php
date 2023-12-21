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
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $abbreviation_de;

    /**
     * The resource's English abbreviation.
     * VARCHAR(25) NOT NULL DEFAULT ''
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $abbreviation_en;

    /**
     * The resource's German description.
     * TEXT
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $description_de;

    /**
     * The resource's English description.
     * TEXT
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $description_en;

    /**
     * The id of the field entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $fieldID;

    /**
     * The resource's German name.
     * VARCHAR(200) DEFAULT NULL
     * @var null|string
     */
    public null|string $fullName_de;

    /**
     * The resource's English name.
     * VARCHAR(200) DEFAULT NULL
     * @var null|string
     */
    public null|string $fullName_en;

    /**
     * The id of the group entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $groupID;

    /**
     * The maximum credit points required to be achieved in subjects of this pool.
     * INT(3) UNSIGNED DEFAULT 0
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $maxCrP;

    /**
     * The minimum credit points required to be achieved in subjects of this pool.
     * INT(3) UNSIGNED DEFAULT 0
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $minCrP;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

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
