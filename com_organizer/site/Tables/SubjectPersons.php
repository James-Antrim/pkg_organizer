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
 * Models the organizer_subject_persons table.
 */
class SubjectPersons extends BaseTable
{
    /**
     * The id of the person entry referenced.
     * INT(11) NOT NULL
     * @var int
     */
    public $personID;

    /**
     * The person's responsibility for the subject. Values: 1 - Coordinates, 2 - Teaches.
     * TINYINT(1) UNSIGNED NOT NULL DEFAULT 1
     * @var int
     */
    public $role;

    /**
     * The id of the subject entry referenced.
     * INT(11) UNSIGNED NOT NULL
     * @var int
     */
    public $subjectID;

    /**
     * Declares the associated table.
     */
    public function __construct()
    {
        parent::__construct('#__organizer_subject_persons');
    }
}
