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
class Grids extends Table
{
    use Coded;

    /**
     * A grid object modeled by a JSON string, containing the respective start and end times of the grid blocks.
     * TEXT
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $grid;

    /**
     * A flag to determine which grid is to be used if none is specified.
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
     * @var bool
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $isDefault;

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
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_grids', 'id', $dbo);
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
