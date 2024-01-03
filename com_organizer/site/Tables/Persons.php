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
class Persons extends Table
{
    use Activated;
    use Aliased;
    use Coded;
    use Suppressed;

    /**
     * The person's first and middle names.
     * VARCHAR(255) NOT NULL DEFAULT ''
     * @var string
     */
    public string $forename = '';

    /**
     * A flag which displays whether the person chooses to display their information publicly.
     * TINYINT(1) UNSIGNED DEFAULT 0
     * @var int
     * @bool
     */
    public int $public = 0;

    /**
     * The person's surnames.
     * VARCHAR(255) NOT NULL
     * @var string
     */
    public string $surname;

    /**
     * The person's titles.
     * VARCHAR(45) NOT NULL DEFAULT ''
     * @var string
     */
    public string $title = '';

    /**
     * The person's username.
     * VARCHAR(150) DEFAULT NULL
     * @var string|null
     */
    public string|null $username;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_persons', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        $nullColumns = ['alias', 'code', 'username'];
        foreach ($nullColumns as $nullColumn) {
            if (!$this->$nullColumn) {
                $this->$nullColumn = null;
            }
        }

        return true;
    }
}
