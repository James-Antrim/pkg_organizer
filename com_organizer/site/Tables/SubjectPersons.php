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

/**
 * @inheritDoc
 */
class SubjectPersons extends Table
{
    /**
     * The id of the person entry referenced.
     * INT(11) NOT NULL
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $personID;

    /**
     * The person's responsibility for the subject. Values: 1 - Coordinates, 2 - Teaches.
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 1
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $role;

    /**
     * The id of the subject entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $subjectID;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_subject_persons', 'id', $dbo);
    }
}
