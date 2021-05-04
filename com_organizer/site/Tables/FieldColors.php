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
 * Models the organizer_event_coordinators table.
 */
class FieldColors extends BaseTable
{
    /**
     * The id of the color entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     *
     * @var int
     */
    public $colorID;

    /**
     * The id of the field entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     *
     * @var int
     */
    public $fieldID;

    /**
     * The id of the organization entry referenced.
     * INT(11) UNSIGNED NOT NULL
     *
     * @var int
     */
    public $organizationID;

    /**
     * Declares the associated table.
     */
    public function __construct()
    {
        parent::__construct('#__organizer_field_colors');
    }
}
