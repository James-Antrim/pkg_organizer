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
class Runs extends Table
{
    use Ends;
    use Localized;

    /**
     * A run object modeled by a JSON string, containing the respective start and end dates of run sections.
     * TEXT
     * @var string
     */
    public string $run;

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
        parent::__construct('#__organizer_runs', 'id', $dbo);
    }
}
