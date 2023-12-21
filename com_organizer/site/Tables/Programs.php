<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @author      Wolf Rost, <wolf.rost@mni.thm.de>
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
class Programs extends Table
{
    use Activated;
    use Aliased;
    use Coded;

    /**
     * The year in which the program was accredited.
     * YEAR(4) DEFAULT NULL
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $accredited;

    /**
     * The id of the category entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $categoryID;

    /**
     * The id of the degree entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $degreeID;

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
     * A flag which displays whether the program has a fee.
     * TINYINT(1) UNSIGNED NOT NULL
     * @var bool
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $fee;

    /**
     * The id of the frequency entry referenced.
     * INT(1) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $frequencyID;

    /**
     * The resource's German name.
     * VARCHAR(150) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $name_de;

    /**
     * The resource's English name.
     * VARCHAR(150) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $name_en;

    /**
     * A flag which displays whether the program has a restricted number of participants.
     * TINYINT(1) UNSIGNED NOT NULL
     * @var bool
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $nc;

    /**
     * The associated organization id.
     * INT(11) UNSIGNED DEFAULT NULL
     *
     * @var int|null
     * @deprecated ???
     */
    public int|null $organizationID;

    /**
     * A flag which displays whether the program has special participation requirements
     * TINYINT(1) UNSIGNED NOT NULL
     * @var bool
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $special;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_programs', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        if (empty($this->alias)) {
            $this->alias = null;
        }

        if (empty($this->categoryID)) {
            $this->categoryID = null;
        }

        return true;
    }
}
