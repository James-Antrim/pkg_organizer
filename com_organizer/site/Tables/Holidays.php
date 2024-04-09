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
use THM\Organizer\Helpers\Holidays as Helper;

/**
 * @inheritDoc
 */
class Holidays extends Table
{
    use Ends;
    use Localized;

    /**
     * The start date of the resource.
     * DATE NOT NULL
     * @var string
     */
    public string $startDate;

    /**
     * The holiday type.
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 3
     * @var int
     * @see Helper::CLOSED, Helper::GAP, Helper::HOLIDAY
     */
    public int $type = Helper::HOLIDAY;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_holidays', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        if ($this->endDate < $this->startDate) {
            return $this->fail();
        }

        return true;
    }
}