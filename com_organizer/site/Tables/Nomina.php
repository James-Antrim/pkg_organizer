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

/** @inheritDoc */
class Nomina extends Table
{
    use Localized;
    use Coded;
    use StatisticCoded;

    /**
     * VARCHAR(255) NOT NULL
     * @var string
     */
    public string $alias_de;

    /**
     * VARCHAR(255) NOT NULL
     * @var string
     */
    public string $alias_en;

    /** @inheritDoc */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::database();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_nomina', 'id', $dbo);
    }
}
