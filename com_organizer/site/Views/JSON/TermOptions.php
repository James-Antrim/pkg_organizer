<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\JSON;

use THM\Organizer\Adapters\Input;
use THM\Organizer\Helpers;

/**
 * Class answers dynamic term related queries
 */
class TermOptions extends BaseView
{
    /**
     * loads model data into view context
     * @return void
     */
    public function display()
    {
        $showDates = Input::getBool('showDates');
        echo json_encode(Helpers\Terms::options($showDates), JSON_UNESCAPED_UNICODE);
    }
}
