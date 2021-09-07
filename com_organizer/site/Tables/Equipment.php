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
 * Models the organizer_buildings table.
 */
class Equipment extends BaseTable
{
    //use Activated;

    public $code;

    /**
     * The id of the campus entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     *
     * @var int
     */
    public $name_en;

    /**
     * The GPS coordinates of the resource.
     * VARCHAR(20) NOT NULL
     *
     * @var string
     */
    public $name_de;

    /**
     * The resource's name.
     * VARCHAR(60) NOT NULL
     *
     * @var string
     */
    public $description_de;

    /**
     * The property type. Values: 0 - New/Unknown | 1 - Owned | 2 - Leased/Rented
     * INT(1) UNSIGNED  NOT NULL DEFAULT 0
     *
     * @var int
     */
    public $description_en;

    /**
     * Declares the associated table.
     */
    public function __construct()
    {
        parent::__construct('#__organizer_equipment');
    }

}
