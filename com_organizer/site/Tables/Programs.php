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

/** @inheritDoc */
class Programs extends Table
{
    use Activated;

    /**
     * YEAR(4) NOT NULL
     * @var int
     */
    public int $accredited;

    /**
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $aTypeID = null;

    /**
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $categoryID = null;

    /**
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $campusID = null;

    /**
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $degreeID = null;

    /**
     * TEXT
     * @var string
     */
    public string $description_de = '';

    /**
     * TEXT
     * @var string
     */
    public string $description_en = '';

    /**
     * A flag which displays whether the program has a fee, not the actual fee.
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
     * @var int
     * @bool
     */
    public int $fee = 0;

    /**
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $focusID = null;

    /**
     * INT(1) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $frequencyID = null;

    /**
     * Numerus clausus.
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
     * @var int
     * @bool
     */
    public int $nc = 0;

    /**
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $minorID = null;

    /**
     * The id of the nomen (base name) for the program.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $nomenID = null;

    /**
     * A flag which displays whether the program has special participation requirements
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
     * @var int
     * @bool
     */
    public int $special = 0;

    /**
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $typeID = null;

    /** @inheritDoc */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::database();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_programs', 'id', $dbo);
    }

    /** @inheritDoc */
    public function check(): bool
    {
        $nullable = ['campusID', 'categoryID', 'focusID', 'formID', 'frequencyID', 'minorID', 'typeID'];
        foreach ($nullable as $property) {
            if (empty($this->$property)) {
                $this->$property = null;
            }
        }

        return true;
    }
}
