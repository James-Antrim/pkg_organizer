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
class Fields extends Table
{
    use Aliased;
    use Localized;

    /**
     * An abbreviated nomenclature for the resource. Currently corresponding to the identifier in Untis scheduling
     * software except units which are also supplemented locally. Collation allows capitalization and accented characters
     * to be accepted as unique entries.
     * VARCHAR(60) NOT NULL COLLATE utf8mb4_bin
     * @var string
     */
    public string $code;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::database();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_fields', 'id', $dbo);
    }
}
