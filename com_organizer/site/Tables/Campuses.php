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
 * Models the organizer_campuses table.
 */
class Campuses extends BaseTable
{
    use Activated;
    use Aliased;

    /**
     * The physical address of the resource.
     * VARCHAR(255) NOT NULL
     *
     * @var string
     */
    public $address;

    /**
     * The city in which the resource is located.
     * VARCHAR(60) NOT NULL
     *
     * @var string
     */
    public $city;

    /**
     * The id of the grid entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     *
     * @var int
     */
    public $gridID;

    /**
     * A flag displaying if the campus is equatable with a city for internal purposes.
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
     *
     * @var bool
     */
    public $isCity;

    /**
     * The GPS coordinates of the resource.
     * VARCHAR(20) NOT NULL
     *
     * @var string
     */
    public $location;

    /**
     * The resource's German name.
     * VARCHAR(60) NOT NULL
     *
     * @var string
     */
    public $name_de;

    /**
     * The resource's English name.
     * VARCHAR(60) NOT NULL
     *
     * @var string
     */
    public $name_en;

    /**
     * The id of the campus entry referenced as parent.
     * INT(11) UNSIGNED DEFAULT NULL
     *
     * @var int
     */
    public $parentID;

    /**
     * The ZIP code of the resource.
     * VARCHAR(60) NOT NULL
     *
     * @var string
     */
    public $zipCode;

    /**
     * Declares the associated table.
     */
    public function __construct()
    {
        parent::__construct('#__organizer_campuses');
    }

    /**
     * @inheritDoc
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function check(): bool
    {
        if (empty($this->alias)) {
            $this->alias = null;
        }

        if (empty($this->gridID)) {
            $this->gridID = null;
        }

        if (empty($this->parentID)) {
            $this->parentID = null;
        }

        return true;
    }
}
