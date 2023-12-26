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
class Methods extends Table
{
    use Aliased;
    use Coded;
    use Relevant;

    /**
     * The resource's German abbreviation.
     * VARCHAR(25) NOT NULL DEFAULT ''
     * @var string
     */
    public string $abbreviation_de = '';

    /**
     * The resource's English abbreviation.
     * VARCHAR(25) NOT NULL DEFAULT ''
     * @var string
     */
    public string $abbreviation_en = '';

    /**
     * The resource's German name.
     * VARCHAR(150) DEFAULT NULL
     * @var null|string
     */
    public null|string $name_de = null;

    /**
     * The resource's English name.
     * VARCHAR(150) DEFAULT NULL
     * @var null|string
     */
    public null|string $name_en = null;

    /**
     * The resource's German plural.
     * VARCHAR(150) DEFAULT ''
     * @var string
     */
    public string $plural_de = '';

    /**
     * The resource's English plural.
     * VARCHAR(150) DEFAULT ''
     * @var string
     */
    public string $plural_en = '';

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
