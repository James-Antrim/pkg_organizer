<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

/** @inheritDoc */
class ImportCourses extends ImportView
{
    // Everything is taken care of in the inheritance hierarchy.

    /** @inheritDoc */
    public function display($tpl = null): void
    {
        $this->toDo[] = 'Add confirmation output with number of newly created courses.';

        parent::display($tpl);
    }
}
