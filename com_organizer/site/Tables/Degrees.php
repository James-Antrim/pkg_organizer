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
 * Models the organizer_degrees table.
 */
class Degrees extends Table
{
    use Activated;
    use Aliased;
    use Coded;

    /**
     * The resource's abbreviation.
     * VARCHAR(50) NOT NULL
     * @var string
     */
    public string $abbreviation;

    /**
     * The resource's name.
     * VARCHAR(150) NOT NULL
     * @var string
     */
    public string $name;

    /**
     * ITS key for statistics for rough categories of degrees. (Bachelor => 84, Master => 90)
     * TINYINT(2) UNSIGNED NOT NULL
     * @var string
     */
    public string $statisticCode;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::database();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_degrees', 'id', $dbo);
    }
}
