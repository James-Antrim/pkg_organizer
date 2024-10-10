<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2024 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use Exception;
use THM\Organizer\Adapters\Input;

/** @inheritDoc */
class Workload extends Controller
{
    /**
     * Generates an Excel spreadsheet.
     * @return void
     * @throws Exception
     */
    public function spreadsheet(): void
    {
        Input::format('xls');
        Input::set('layout', 'Workload');
        parent::display();
    }
}