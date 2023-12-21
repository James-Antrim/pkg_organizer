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
class Terms extends Table
{
    use Aliased;
    use Coded;

    /**
     * The end date of the resource.
     * DATE DEFAULT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $endDate;

    /**
     * The resource's full German name.
     * VARCHAR(200) DEFAULT ''
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $fullName_de;

    /**
     * The resource's full English name.
     * VARCHAR(200) DEFAULT ''
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $fullName_en;

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
     * The start date of the resource.
     * DATE DEFAULT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
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
