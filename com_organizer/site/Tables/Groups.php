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
class Groups extends Table
{
    use Activated;
    use Aliased;
    use Coded;
    use Localized;
    use Suppressed;

    /**
     * The id of the category entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public int $categoryID;

    /**
     * The resource's German name.
     * VARCHAR(200) NOT NULL
     * @var string
     */
    public string $fullName_de;

    /**
     * The resource's English name.
     * VARCHAR(200) NOT NULL
     * @var string
     */
    public string $fullName_en;

    /**
     * The id of the grid entry referenced.
     * INT(11) UNSIGNED DEFAULT 1
     * @var int
     */
    public int $gridID = 1;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_groups', 'id', $dbo);
    }
}
