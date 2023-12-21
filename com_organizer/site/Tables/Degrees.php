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
     * VARCHAR(45) NOT NULL DEFAULT ''
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $abbreviation;

    /**
     * The resource's name.
     * VARCHAR(255) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $name;

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
