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
     * The id of the campus entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $campusID;

    /**
     * The number of days before course begin when registration is closed.
     * INT(2) UNSIGNED DEFAULT 0
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $deadline;

    /**
     * The resource's German description.
     * TEXT
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $description_de;

    /**
     * The resource's English description.
     * TEXT
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $description_en;

    /**
     * The fee for participation in the course.
     * INT(3) UNSIGNED DEFAULT 0
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $fee;

    /**
     * A short textual description of which groups should visit the course.
     * VARCHAR(100) NOT NULL DEFAULT ''
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $groups;

    /**
     * The maximum number of participants the course allows.
     * INT(4) UNSIGNED DEFAULT 1000
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $maxParticipants;

    /**
     * The resource's German name.
     * VARCHAR(150) DEFAULT NULL
     * @var string|null
     */
    public null|string $name_de;

    /**
     * The resource's English name.
     * VARCHAR(150) DEFAULT NULL
     * @var null|string
     */
    public null|string $name_en;

    /**
     * The method of processing used to accept course registrations. Values: NULL - None, 0 - FIFO, 1 - Manual.
     * INT(1) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $registrationType;

    /**
     * The id of the term entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $termID;

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
