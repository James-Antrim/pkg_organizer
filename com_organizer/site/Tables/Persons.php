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
 * Models the organizer_persons table.
 */
class Persons extends BaseTable
{
    use Activated;
    use Aliased;
    use Coded;
    use Suppressed;

    /**
     * The person's first and middle names.
     * VARCHAR(255) NOT NULL DEFAULT ''
     * @var string
     */
    public $forename;

    /**
     * A flag which displays whether the person chooses to display their information publicly.
     * TINYINT(1) UNSIGNED DEFAULT 0
     * @var string
     */
    public $public;

    /**
     * The person's surnames.
     * VARCHAR(255) NOT NULL
     * @var string
     */
    public $surname;

    /**
     * The person's titles.
     * VARCHAR(45) NOT NULL DEFAULT ''
     * @var string
     */
    public $title;

    /**
     * The person's user name.
     * VARCHAR(150) DEFAULT NULL
     * @var string
     */
    public $username;

    /**
     * Declares the associated table.
     */
    public function __construct()
    {
        parent::__construct('#__organizer_persons');
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        // All three fields can receive data from at least two systems.
        $nullColumns = ['alias', 'code', 'username'];
        foreach ($nullColumns as $nullColumn) {
            if (!strlen($this->$nullColumn)) {
                $this->$nullColumn = null;
            }
        }

        return true;
    }
}
