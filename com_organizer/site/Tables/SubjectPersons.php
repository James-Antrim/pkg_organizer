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

use Joomla\Database\{DatabaseDriver, DatabaseInterface};
use THM\Organizer\Adapters\Application;
use THM\Organizer\Helpers\Persons;

/**
 * @inheritDoc
 */
class SubjectPersons extends Table
{
    /**
     * The id of the person entry referenced.
     * INT(11) NOT NULL
     * @var int
     */
    public int $personID;

    /**
     * The person's responsibility for the subject. Values: 1 - Coordinates, 2 - Teaches.
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 1
     * @var int
     */
    public int $role = Persons::COORDINATES;

    /**
     * The id of the subject entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public int $subjectID;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::database();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_subject_persons', 'id', $dbo);
    }
}
