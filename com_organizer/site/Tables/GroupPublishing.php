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
class GroupPublishing extends Table
{
    /**
     * The id of the group entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public int $groupID;

    /**
     * The publishing status of the group for the term.
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 1
     * @var int
     * @bool
     */
    public int $published = 1;

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
        $dbo = $dbo ?? Application::database();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_group_publishing', 'id', $dbo);
    }
}
