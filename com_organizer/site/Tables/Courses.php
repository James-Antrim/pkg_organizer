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
class Courses extends Table
{
    use Aliased;

    /**
     * The id of the campus entry referenced. Null is available as the referenced purposefully does not cascade on delete.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $campusID = null;

    /**
     * The number of days before course begin when registration is closed.
     * INT(2) UNSIGNED DEFAULT 0
     * @var int
     */
    public int $deadline = 0;

    /**
     * The resource's German description.
     * TEXT
     * @var string
     */
    public string $description_de;

    /**
     * The resource's English description.
     * TEXT
     * @var string
     */
    public string $description_en;

    /**
     * The fee for participation in the course.
     * INT(3) UNSIGNED DEFAULT 0
     * @var int
     */
    public int $fee = 0;

    /**
     * A short textual description of which groups should visit the course.
     * VARCHAR(100) NOT NULL DEFAULT ''
     * @var string
     */
    public string $groups = '';

    /**
     * The maximum number of participants the course allows.
     * INT(4) UNSIGNED DEFAULT 1000
     * @var int
     */
    public int $maxParticipants = 1000;

    /**
     * The resource's German name.
     * VARCHAR(150) DEFAULT NULL
     * @var string|null
     */
    public string|null $name_de = null;

    /**
     * The resource's English name.
     * VARCHAR(150) DEFAULT NULL
     * @var string|null
     */
    public string|null $name_en = null;

    /**
     * The method of processing used to accept course registrations. Values: NULL - None, 0 - FIFO, 1 - Manual.
     * INT(1) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $registrationType = null;

    /**
     * The id of the term entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public int $termID;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_courses', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        if (empty($this->alias)) {
            $this->alias = null;
        }

        if (empty($this->campusID)) {
            $this->campusID = null;
        }

        if (empty($this->name_de)) {
            $this->name_de = null;
        }

        if (empty($this->name_en)) {
            $this->name_en = null;
        }

        return true;
    }
}
