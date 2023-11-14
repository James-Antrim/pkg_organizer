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

/**
 * Models the organizer_roomtypes table.
 */
class RoomTypes extends BaseTable
{
    use Suppressed;

    /**
     * The resource's German description.
     * TEXT
     * @var string
     */
    public $description_de;

    /**
     * The resource's English description.
     * TEXT
     * @var string
     */
    public $description_en;

    /**
     * The maximum occupancy for rooms of this type.
     * INT(4) UNSIGNED DEFAULT NULL
     * @var string
     */
    public $capacity;

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
     * The id of the normed use code with which the type is associated.
     * SMALLINT(4) UNSIGNED NOT NULL
     * @var int
     */
    public $usecode;

    /**
     * Declares the associated table.
     */
    public function __construct()
    {
        parent::__construct('#__organizer_roomtypes');
    }
}
