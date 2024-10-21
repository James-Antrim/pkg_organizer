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
use THM\Organizer\Helpers\Roles;

/**
 * @inheritDoc
 */
class InstancePersons extends Table
{
    use Modified;

    /**
     * INT(20) UNSIGNED NOT NULL AUTO_INCREMENT
     *
     * @var int
     */
    public int $id;

    /**
     * The id of the instance entry referenced.
     * INT(20) UNSIGNED NOT NULL
     * @var int
     */
    public int $instanceID;

    /**
     * The id of the person entry referenced.
     * INT(11) NOT NULL
     * @var int
     */
    public int $personID;

    /**
     * The id of the role entry referenced.
     * TINYINT(2) UNSIGNED NOT NULL DEFAULT 1
     * @var int
     */
    public int $roleID = Roles::TEACHER;


    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::database();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_instance_persons', 'id', $dbo);
    }
}
