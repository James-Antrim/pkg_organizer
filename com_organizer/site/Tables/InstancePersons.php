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
class InstancePersons extends Table
{
    use Modified;

    /**
     * The id of the instance entry referenced.
     * INT(20) UNSIGNED NOT NULL
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $instanceID;

    /**
     * The id of the person entry referenced.
     * INT(11) NOT NULL
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $personID;

    /**
     * The id of the role entry referenced.
     * TINYINT(2) UNSIGNED NOT NULL DEFAULT 1
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $roleID;

    /**
     * Declares the associated table.
     */
    public function __construct()
    {
        parent::__construct('#__organizer_instance_persons');
    }
}
