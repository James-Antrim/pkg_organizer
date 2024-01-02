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
class Prerequisites extends Table
{
    /**
     * The id of the subject entry referenced as being a dependency.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public int $prerequisiteID;

    /**
     * The id of the subject entry referenced as requiring a dependency.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public int $subjectID;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_prerequisites', 'id', $dbo);
    }
}
