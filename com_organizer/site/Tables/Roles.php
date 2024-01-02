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
class Roles extends Table
{
    use Coded;
    use Localized;

    /**
     * The resource's German abbreviation.
     * VARCHAR(25) NOT NULL
     * @var string
     */
    public string $abbreviation_de;

    /**
     * The resource's English abbreviation.
     * VARCHAR(25) NOT NULL
     * @var string
     */
    public string $abbreviation_en;

    /**
     * TINYINT(2) UNSIGNED NOT NULL AUTO_INCREMENT
     *
     * @var int
     */
    public int $id;

    /**
     * The resource's German plural.
     * VARCHAR(150) NOT NULL
     * @var string
     */
    public string $plural_de;

    /**
     * The resource's English plural.
     * VARCHAR(150) NOT NULL
     * @var string
     */
    public string $plural_en;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_roles', 'id', $dbo);
    }
}
