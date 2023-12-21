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

    /**
     * The resource's German abbreviation.
     * VARCHAR(25) NOT NULL DEFAULT ''
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $abbreviation_de;

    /**
     * The resource's English abbreviation.
     * VARCHAR(25) NOT NULL DEFAULT ''
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $abbreviation_en;

    /**
     * The resource's German name.
     * VARCHAR(150) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $name_de;

    /**
     * The resource's English name.
     * VARCHAR(150) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $name_en;

    /**
     * The resource's German plural.
     * VARCHAR(150) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $plural_de;

    /**
     * The resource's English plural.
     * VARCHAR(150) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $plural_en;

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
