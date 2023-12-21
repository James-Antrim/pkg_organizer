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
 * Models the organizer_holidays table.
 */
class Holidays extends Table
{
    /**
     * The end date of the resource.
     * DATE DEFAULT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $endDate;

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
     * The start date of the resource.
     * DATE DEFAULT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $startDate;

    /**
     * The impact of the holiday on the planning process. Values: 1 - Automatic, 2 - Manual, 3 - Unplannable
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 3
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $type;

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
            Application::message('ORGANIZER_DATE_CHECK', Application::ERROR);

            return false;
        }

        return true;
    }
}