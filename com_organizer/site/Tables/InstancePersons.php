<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Tables;

/**
 * Models the organizer_instance_persons table.
 */
class InstancePersons extends BaseTable
{
    use Modified;

    /**
     * The id of the instance entry referenced.
     * INT(20) UNSIGNED NOT NULL
     *
     * @var int
     */
    public $instanceID;

    /**
     * The id of the person entry referenced.
     * INT(11) NOT NULL
     *
     * @var int
     */
    public $personID;

    /**
     * The id of the role entry referenced.
     * TINYINT(2) UNSIGNED NOT NULL DEFAULT 1
     *
     * @var int
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
