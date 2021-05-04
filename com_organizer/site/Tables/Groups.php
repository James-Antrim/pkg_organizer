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
 * Models the organizer_groups table.
 */
class Groups extends BaseTable
{
    use Activated;
    use Aliased;
    use Coded;
    use Suppressed;

    /**
     * The id of the category entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     *
     * @var int
     */
    public $categoryID;

    /**
     * The resource's German name.
     * VARCHAR(200) NOT NULL
     *
     * @var string
     */
    public $fullName_de;

    /**
     * The resource's English name.
     * VARCHAR(200) NOT NULL
     *
     * @var string
     */
    public $fullName_en;

    /**
     * The id of the grid entry referenced.
     * INT(11) UNSIGNED DEFAULT 1
     *
     * @var int
     */
    public $gridID;

    /**
     * The resource's German name.
     * VARCHAR(150) NOT NULL
     *
     * @var string
     */
    public $name_de;

    /**
     * The resource's English name.
     * VARCHAR(150) NOT NULL
     *
     * @var string
     */
    public $name_en;

    /**
     * Declares the associated table.
     */
    public function __construct()
    {
        parent::__construct('#__organizer_groups');
    }
}
