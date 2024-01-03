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
class Events extends Table
{
    use Activated;
    use Aliased;
    use Localized;
    use Suppressed;

    /**
     * The id of the campus entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $campusID = null;

    /**
     * An abbreviated nomenclature for the resource. Currently corresponding to the identifier in Untis scheduling
     * software except units which are also supplemented locally. Collation allows capitolization and accented characters
     * to be accepted as unique entries.
     * VARCHAR(60) NOT NULL COLLATE utf8mb4_bin
     * @var string
     */
    public string $code;

    /**
     * The organization's German contact information for a group of courses.
     * TEXT
     * @var string
     */
    public string $contact_de;

    /**
     * The organization's English contact information for a group of courses.
     * TEXT
     * @var string
     */
    public string $contact_en;

    /**
     * The German description of the event's contents.
     * TEXT
     * @var string
     */
    public string $content_de;

    /**
     * The English description of the event's contents.
     * TEXT
     * @var string
     */
    public string $content_en;

    /**
     * The organization's German contact information for courses of this event type.
     * TEXT
     * @var string
     */
    public string $courseContact_de;

    /**
     * The organization's English contact information for courses of this event type.
     * TEXT
     * @var string
     */
    public string $courseContact_en;

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
     * The maximum number of participants the course allows.
     * INT(4) UNSIGNED DEFAULT 1000
     * @var int
     */
    public int $maxParticipants = 1000;

    /**
     * A German description of how courses implementing this event are organized.
     * TEXT
     * @var string
     */
    public string $organization_de;

    /**
     * An English description of how courses implementing this event are organized.
     * TEXT
     * @var string
     */
    public string $organization_en;

    /**
     * The id of the organization entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int
     */
    public int $organizationID;

    /**
     * A German description how to test one's self to see if one should attend or is qualified to attend courses
     * implementing this event.
     * TEXT
     * @var string
     */
    public string $pretests_de;

    /**
     * An English description how to test one's self to see if one should attend or is qualified to attend courses
     * implementing this event.
     * TEXT
     * @var string
     */
    public string $pretests_en;

    /**
     * Whether the event is a preparatory event.
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
     * @var int
     * @bool
     */
    public int $preparatory = 0;

    /**
     * The method of processing used to accept course registrations. Values: NULL - None, 0 - FIFO, 1 - Manual.
     * INT(1) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $registrationType = null;

    /**
     * The resource's alphanumeric identifier in degree program documentation.
     * VARCHAR(45) NOT NULL DEFAULT ''
     * @var string
     */
    public string $subjectNo = '';

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_events', 'id', $dbo);
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

        if (empty($this->registrationType) or !is_numeric($this->registrationType)) {
            $this->registrationType = null;
        }

        return true;
    }
}
