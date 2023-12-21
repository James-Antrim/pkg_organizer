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

use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use THM\Organizer\Adapters\Application;

/**
 * Models the organizer_runs table.
 */
class Runs extends Table
{
    /**
     * The end date of the resource.
     * DATE NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $endDate;

    /**
     * The resource's German name.
     * VARCHAR(150) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $name_de;

    /**
     * The resource's English name.
     * VARCHAR(150) NOT NULL
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $name_en;

    /**
     * A run object modeled by a JSON string, containing the respective start and end dates of run sections.
     * TEXT
     * @var string
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $run;

    /**
     * The id of the term entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $termID;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_runs', 'id', $dbo);
    }
}
