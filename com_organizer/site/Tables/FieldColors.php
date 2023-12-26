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
class FieldColors extends Table
{
    /**
     * The id of the color entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int
     */
    public int $colorID;

    /**
     * The id of the field entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int
     */
    public int $fieldID;

    /**
     * The id of the organization entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public int $organizationID;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_field_colors', 'id', $dbo);
    }
}
