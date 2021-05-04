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
 * Models the organizer_colors table.
 */
class Colors extends BaseTable
{
    /**
     * The six digit hexadecimal value of the color with leading #.
     * VARCHAR(60) NOT NULL
     *
     * @var string
     */
    public $color;

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
     * Declares the associated table.
     */
    public function __construct()
    {
        parent::__construct('#__organizer_colors');
    }
}
