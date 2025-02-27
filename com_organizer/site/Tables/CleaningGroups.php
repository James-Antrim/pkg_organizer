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
    use Localized;
    use Relevant;

    /**
     * The number of days per month used for calculated values.
     * DOUBLE(6, 2) UNSIGNED NOT NULL
     * @var float
     */
    public float $days;

    /**
     * TINYINT(2) UNSIGNED NOT NULL AUTO_INCREMENT
     *
     * @var int
     */
    public int $id;

    /**
     * The max valuation of the cleaning quality for this group. This value appears in exported media but is not overtly
     * used to calculate the actual valuation. ~Overhead
     * SMALLINT(3) UNSIGNED
     * @var int
     */
    public int $maxValuation;

    /**
     * The numeric valuation of cleaning quality.
     * DOUBLE(6, 2) UNSIGNED NOT NULL
     * @var float
     */
    public float $valuation;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::database();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_cleaning_groups', 'id', $dbo);
    }
}
