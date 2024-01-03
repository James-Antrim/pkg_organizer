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
class Units extends Table
{
    use Coded;
    use Modified;

    /**
     * A supplementary text description.
     * VARCHAR(255) DEFAULT ''
     * @var string
     */
    public string $comment = '';

    /**
     * The id of the course entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $courseID;

    /**
     * The id of the organization entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $organizationID;

    /**
     * The end date of the resource.
     * DATE DEFAULT NULL
     * @var string|null
     */
    public string|null $endDate;

    /**
     * The id of the grid entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $gridID;

    /**
     * The id of the run entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $runID;

    /**
     * The start date of the resource.
     * DATE DEFAULT NULL
     * @var string|null
     */
    public string|null $startDate;

    /**
     * The id of the term entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int|null
     */
    public int|null $termID;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_units', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        $nullColumns = ['courseID', 'endDate', 'gridID', 'runID', 'startDate'];

        foreach ($nullColumns as $nullColumn) {
            if (!$this->$nullColumn) {
                $this->$nullColumn = null;
            }
        }

        if ($this->modified === '0000-00-00 00:00:00') {
            $this->modified = null;
        }

        return true;
    }
}
