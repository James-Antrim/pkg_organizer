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
class Equipment extends Table
{
    use Coded;

    /**
     * The equipment's German name.
     * VARCHAR(150) DEFAULT NULL
     * @var null|string
     */
    public null|string $name_de;

    /**
     * The equipment's English name.
     * VARCHAR(150) DEFAULT NULL
     * @var null|string
     */
    public null|string $name_en;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_equipment', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        if (empty($this->code)) {
            $this->code = null;
        }

        if (!$this->name_de or !$this->name_en) {
            return false;
        }

        return true;
    }
}
