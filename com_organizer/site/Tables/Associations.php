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
class Associations extends Table
{
    /**
     * The id of the category entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $categoryID;

    /**
     * The id of the group entry referenced.
     * INT(11) DEFAULT NULL
     * @var int|null
     */
    public int|null $groupID;

    /**
     * The id of the organization entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public int $organizationID;

    /**
     * The id of the person entry referenced.
     * INT(11) DEFAULT NULL
     * @var int|null
     */
    public int|null $personID;

    /**
     * The id of the pool entry referenced.
     * INT(11) DEFAULT NULL
     * @var int|null
     */
    public int|null $poolID;

    /**
     * The id of the program entry referenced.
     * INT(11) DEFAULT NULL
     * @var int|null
     */
    public int|null $programID;

    /**
     * The id of the subject entry referenced.
     * INT(11) DEFAULT NULL
     * @var int|null
     */
    public int|null $subjectID;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_associations', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        if (empty($this->organizationID)) {
            return $this->fail();
        }

        // An association should always be between an organization and another resource.
        $count      = 0;
        $keyColumns = ['categoryID', 'groupID', 'personID', 'poolID', 'programID', 'subjectID'];
        foreach ($keyColumns as $keyColumn) {
            if ($this->$keyColumn) {
                $count++;
            }
            else {
                $this->$keyColumn = null;
            }
        }

        if ($count !== 1) {
            return $this->fail();
        }

        return true;
    }
}
