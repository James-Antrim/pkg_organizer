<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use Exception;
use THM\Organizer\Adapters\Input;

/** @inheritDoc */
class Courses extends ListController
{
    protected string $item = 'Course';

    /**
     * Prints badges for the selected participants.
     * @return void
     * @throws Exception
     */
    public function badge(): void
    {
        Input::set('format', 'pdf');
        Input::set('layout', 'Badge');
        parent::display();
    }

    /**
     * Redirects to the form view for the creation of a new resource.
     * @return void
     */
    public function import(): void
    {
        $this->setRedirect("$this->baseURL&view=importcourses");
    }

    /**
     * Opens the course participants view for the selected course.
     * @return void
     * @throws Exception
     */
    public function participants(): void
    {
        if (!$courseID = Input::getSelectedIDs()[0]) {
            parent::display();

            return;
        }

        $this->setRedirect("$this->baseURL&view=courseparticipants&courseID=$courseID");
    }
}
