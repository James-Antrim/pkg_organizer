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
class Rooms extends ListController
{
    use Activated;
    use FluMoxed;

    protected string $item = 'Room';

    /**
     * Creates an UniNow xls file based on form data.
     * @return void
     * @throws Exception
     */
    public function uniNow(): void
    {
        Input::format('xls');
        Input::set('layout', 'UniNow');
        $this->display();
    }

    /**
     * Creates a xls file based on form data.
     * @return void
     * @throws Exception
     */
    public function xls(): void
    {
        Input::format('xls');
        $this->display();
    }
}
