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

/**
 * Models the organizer_pools table.
 */
class Pools extends BaseTable
{
    use Aliased;
    use LSFImported;

    /**
     * The resource's German abbreviation.
     * VARCHAR(25) NOT NULL DEFAULT ''
     * @var string
     */
    public $abbreviation_de;

    /**
     * The resource's English abbreviation.
     * VARCHAR(25) NOT NULL DEFAULT ''
     * @var string
     */
    public $abbreviation_en;

    /**
     * The resource's German description.
     * TEXT
     * @var string
     */
    public $description_de;

    /**
     * The resource's English description.
     * TEXT
     * @var string
     */
    public $description_en;

    /**
     * The id of the field entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int
     */
    public $fieldID;

    /**
     * The resource's German name.
     * VARCHAR(255) NOT NULL
     * @var string
     */
    public $fullName_de;

    /**
     * The resource's English name.
     * VARCHAR(255) NOT NULL
     * @var string
     */
    public $fullName_en;

    /**
     * The id of the group entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int
     */
    public $groupID;

    /**
     * The id of the entry in the LSF software module.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int
     */
    public $lsfID;

    /**
     * The maximum credit points required to be achieved in subjects of this pool.
     * INT(3) UNSIGNED DEFAULT 0
     * @var int
     */
    public $maxCrP;

    /**
     * The minimum credit points required to be achieved in subjects of this pool.
     * INT(3) UNSIGNED DEFAULT 0
     * @var int
     */
    public $minCrP;

    /**
     * Declares the associated table.
     */
    public function __construct()
    {
        parent::__construct('#__organizer_pools');
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
