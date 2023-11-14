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

use THM\Organizer\Helpers;

/**
 * Class answers dynamic (degree) program related queries
 */
class RoomTypes extends BaseView
{
    /**
     * loads model data into view context
     * @return void
     */
    public function display(): void
    {
        echo json_encode(Helpers\RoomTypes::getResources(), JSON_UNESCAPED_UNICODE);
    }
}
