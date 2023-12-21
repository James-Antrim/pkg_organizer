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
class CleaningGroups extends Table
{
    /**
     * The number of days per month used for calculated values.
     * DOUBLE(6, 2) UNSIGNED NOT NULL
     * @var float
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $days;

    /**
     * The cleaning group's German name.
     * VARCHAR(150) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $name_de;

    /**
     * The cleaning group's English name.
     * VARCHAR(150) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $name_en;

    /**
     * The max valuation of the cleaning quality for this group. This value appears in exported media but is not overtly
     * used to calculate the actual valuation. ~Overhead
     * SMALLINT(3) UNSIGNED
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $maxValuation;

    /**
     * A flag which displays whether associated rooms should appear in exported media.
     * TINYINT(1) UNSIGNED NOT NULL
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $relevant;

    /**
     * The numeric valuation of cleaning quality.
     * DOUBLE(6, 2) UNSIGNED NOT NULL
     * @var float
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $valuation;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_cleaning_groups', 'id', $dbo);
    }
}
