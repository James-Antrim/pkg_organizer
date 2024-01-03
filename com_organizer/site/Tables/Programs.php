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
    use Localized;

    /**
     * The year in which the program was accredited.
     * YEAR(4) NOT NULL
     * @var int
     */
    public int $accredited;

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
     */
    public string $description_de = '';

    /**
     * The resource's English description.
     * TEXT
     * @var string
     */
    public string $description_en = '';

    /**
     * A flag which displays whether the program has a fee.
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
     * @var int
     * @bool
     */
    public int $fee = 0;

    /**
     * The id of the frequency entry referenced.
     * INT(1) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $frequencyID;

    /**
     * A flag which displays whether the program has a restricted number of participants.
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
     * @var int
     * @bool
     */
    public int $nc = 0;

    /**
     * The associated organization id.
     * INT(11) UNSIGNED DEFAULT NULL
     *
     * @var int|null
     * @deprecated is this still filled to show priority or was this replaced completely by the associations table?
     */
    public int|null $organizationID;

    /**
     * A flag which displays whether the program has special participation requirements
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
     * @var int
     * @bool
     */
    public int $special = 0;

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
