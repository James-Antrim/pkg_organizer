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
 * Models the organizer_methods table.
 * @noinspection PhpUnused
 */
class Methods extends Table
{
    use Aliased;
    use Coded;
    use Nullable;

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
     * @var null|string
     */
    public null|string $name_de;

    /**
     * The resource's English name.
     * VARCHAR(150) NOT NULL
     * @var null|string
     */
    public null|string $name_en;

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
     * A flag which displays whether the method is relevant for .
     * TINYINT(1) UNSIGNED NOT NULL
     * @var bool
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $relevant;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_methods', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        // An association should always be between an organization and another resource.
        $columns = ['name_de', 'name_en'];
        foreach ($columns as $column) {
            if (empty($this->$column)) {
                $this->$column = null;
            }
        }

        return true;
    }
}
