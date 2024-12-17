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
class Instances extends Table
{
    use Modified;

    /**
     * The number of participants who checked into this instance.
     * INT(4) UNSIGNED NOT NULL DEFAULT 0
     * @var int
     */
    public int $attended = 0;

    /**
     * The id of the block entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public int $blockID;

    /**
     * The number of participants who added this instance to their personal schedule.
     * INT(4) UNSIGNED NOT NULL DEFAULT 0
     * @var int
     */
    public int $bookmarked = 0;

    /**
     * A supplementary text description specific to a subset of unit instances.
     * VARCHAR(255) DEFAULT ''
     * @var string
     */
    public string $comment = '';

    /**
     * The id of the event entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $eventID = null;

    /**
     * INT(20) UNSIGNED NOT NULL AUTO_INCREMENT
     *
     * @var int
     */
    public int $id;

    /**
     * The id of the method entry referenced. Independent of FK cascading, this can legitimately not reference a method.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $methodID = null;

    /**
     * Whether the specific instance is published.
     * TINYINT(1) UNSIGNED NOT NULL
     * @var int
     */
    public int $published = 1;

    /**
     * The number of participants who registered to participate in this instance.
     * INT(4) UNSIGNED NOT NULL DEFAULT 0
     * @var int
     */
    public int $registered = 0;

    /**
     * The person's first and middle names.
     * VARCHAR(255) NOT NULL DEFAULT ''
     * @var string
     */
    public string $title = '';

    /**
     * The id of the unit entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public int $unitID;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::database();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_instances', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        if (empty($this->eventID)) {
            $this->methodID = null;
        }

        if (empty($this->methodID)) {
            $this->methodID = null;
        }

        if ($this->modified === '0000-00-00 00:00:00') {
            $this->modified = null;
        }

        return true;
    }
}
