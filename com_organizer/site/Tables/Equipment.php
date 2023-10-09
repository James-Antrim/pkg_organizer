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
class Equipment extends BaseTable
{
    use Coded;

    /**
     * The equipment's German name.
     * VARCHAR(150) NOT NULL
     * @var string
     */
    public $name_de;

    /**
     * The equipment's English name.
     * VARCHAR(150) NOT NULL
     * @var string
     */
    public $name_en;

    /**
     * Declares the associated table.
     */
    public function __construct()
    {
        parent::__construct('#__organizer_equipment');
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        if (empty($this->code)) {
            $this->code = null;
        }

        return true;
    }
}
