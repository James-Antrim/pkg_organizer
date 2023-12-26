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
    use Aliased;
    use Coded;

    /**
     * The resource's abbreviation.
     * VARCHAR(25) NOT NULL
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
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_degrees', 'id', $dbo);
    }
}
