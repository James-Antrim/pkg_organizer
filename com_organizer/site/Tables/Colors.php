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
class Colors extends Table
{
    /**
     * The six digit hexadecimal value of the color with leading #.
     * VARCHAR(60) NOT NULL
     * @var string
     */
    public string $color;

    /**
     * The resource's German name.
     * VARCHAR(60) NOT NULL
     * @var string
     */
    public string $name_de;

    /**
     * The resource's English name.
     * VARCHAR(60) NOT NULL
     * @var string
     */
    public string $name_en;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_colors', 'id', $dbo);
    }
}
