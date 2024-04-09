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
class Curricula extends Table
{
    /**
     * The depth of this element in the curriculum hierarchy.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $level = null;

    /**
     * The left most value of this resource as viewed on a numbered line.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $lft = null;

    /**
     * The order of this element among its hierarchical siblings.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $ordering = null;

    /**
     * The id of the range referenced as parent.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $parentID = null;

    /**
     * The id of the pool entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $poolID = null;

    /**
     * The id of the program entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $programID = null;

    /**
     * The right most value of this resource as viewed on a numbered line.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $rgt = null;

    /**
     * The id of the subject entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $subjectID = null;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_curricula', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        // Although typing allows it these columns should never be null
        foreach (['level', 'lft', 'ordering', 'rgt'] as $column) {
            if (!$this->$column) {
                return $this->fail();
            }
        }

        // Should be null for programs
        if (!$this->parentID) {
            $this->parentID = null;
        }

        $count      = 0;
        $keyColumns = ['programID', 'poolID', 'subjectID'];
        foreach ($keyColumns as $keyColumn) {
            if ($this->$keyColumn) {
                $count++;
            }
            else {
                $this->$keyColumn = null;
                continue;
            }

            if ($keyColumn === 'programID') {
                if ($this->parentID) {
                    return $this->fail();
                }
            }
            elseif (!$this->parentID) {
                return $this->fail();
            }
        }

        if ($count !== 1) {
            return $this->fail();
        }

        return true;
    }
}
