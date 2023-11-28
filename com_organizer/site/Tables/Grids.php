<?php /** @noinspection PhpMissingFieldTypeInspection */

/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Tables;

use Joomla\CMS\Table\Table;
use Joomla\Database\{DatabaseDriver, DatabaseInterface};
use THM\Organizer\Adapters\Application;

/**
 * Models the organizer_grids table.
 */
class Grids extends Table
{
    use Coded;
    use Incremented;

    /**
     * A grid object modeled by a JSON string, containing the respective start and end times of the grid blocks.
     * TEXT
     * @var string
     */
    public $grid;

    /**
     * A flag to determine which grid is to be used if none is specified.
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
     * @var bool
     */
    public $isDefault;

    /**
     * The resource's German name.
     * VARCHAR(150) NOT NULL
     * @var string
     */
    public $name_de;

    /**
     * The resource's English name.
     * VARCHAR(150) NOT NULL
     * @var string
     */
    public $name_en;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_grids', 'id', $dbo);
    }
}
