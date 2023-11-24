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
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use THM\Organizer\Adapters\Application;

/**
 * Models the organizer_terms table.
 */
class Terms extends Table
{
    use Aliased;
    use Coded;

    /**
     * The end date of the resource.
     * DATE DEFAULT NULL
     * @var string
     */
    public $endDate;

    /**
     * The resource's full German name.
     * VARCHAR(200) DEFAULT ''
     * @var string
     */
    public $fullName_de;

    /**
     * The resource's full English name.
     * VARCHAR(200) DEFAULT ''
     * @var string
     */
    public $fullName_en;

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
     * The start date of the resource.
     * DATE DEFAULT NULL
     * @var string
     */
    public $startDate;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_terms', 'id', $dbo);
    }
}
