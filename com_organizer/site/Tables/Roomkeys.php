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

/**
 * Models the organizer_blocks table.
 */
class Roomkeys extends BaseTable
{
    /**
     * The cleaning group associated with the room key.
     * TINYINT(2) UNSIGNED  DEFAULT NULL
     * @var int
     */
    public $cleaningID;

    /**
     * The actual room key.
     * VARCHAR(3) NOT NULL
     * @var string
     */
    public $key;

    /**
     * The room key's German name.
     * VARCHAR(150) NOT NULL
     * @var string
     */
    public $name_de;

    /**
     * The room key's English name.
     * VARCHAR(150) NOT NULL
     * @var string
     */
    public $name_en;

    /**
     * The use group associated with the room key.
     * TINYINT(1) UNSIGNED  NOT NULL
     * @var int
     */
    public $useID;

    /**
     * Declares the associated table.
     */
    public function __construct()
    {
        parent::__construct('#__organizer_roomkeys');
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        if (empty($this->cleaningID)) {
            $this->cleaningID = null;
        }

        return true;
    }
}
