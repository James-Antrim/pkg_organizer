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
class Instances extends ListController
{
    use Booked;

    /**
     * Prints badges for the selected participants.
     * @return void
     * @throws Exception
     */
    public function gridA3(): void
    {
        Input::format('pdf');
        Input::set('layout', 'GridA3');
        parent::display();
    }

    /**
     * Prints badges for the selected participants.
     * @return void
     * @throws Exception
     */
    public function gridA4(): void
    {
        Input::format('pdf');
        Input::set('layout', 'GridA4');
        parent::display();
    }

    /**
     * Creates a xls file based on form data.
     * @return void
     * @throws Exception
     */
    public function xls(): void
    {
        // prevents parameter name from biting here
        Input::format('xls');
        Input::set('layout', 'Instances');
        $this->display();
    }
}
