<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Tables;

/**
 * Models the organizer_blocks table.
 */
class CleaningGroups extends BaseTable
{
    /**
     * The number of days per month used for calculated values.
     * DOUBLE(6, 2) UNSIGNED NOT NULL
     * @var float
     */
    public $days;

    /**
     * The cleaning group's German name.
     * VARCHAR(150) NOT NULL
     * @var string
     */
    public $name_de;

    /**
     * The cleaning group's English name.
     * VARCHAR(150) NOT NULL
     * @var string
     */
    public $name_en;

    /**
     * The max valuation of the cleaning quality for this group. This value appears in exported media but is not overtly
     * used to calculate the actual valuation. ~Overhead
     * SMALLINT(3) UNSIGNED
     * @var int
     */
    public $maxValuation;

    /**
     * A flag which displays whether associated rooms should appear in exported media.
     * TINYINT(1) UNSIGNED NOT NULL
     * @var int
     */
    public $relevant;

    /**
     * The numeric valuation of cleaning quality.
     * DOUBLE(6, 2) UNSIGNED NOT NULL
     * @var float
     */
    public $valuation;

    /**
     * Declares the associated table.
     */
    public function __construct()
    {
        parent::__construct('#__organizer_cleaning_groups');
    }
}
