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
 * Class answers dynamic term related queries
 */
class Instances extends BaseView
{
    /**
     * loads model data into view context
     * @return void
     */
    public function display()
    {
        $conditions = Helpers\Instances::getConditions();
        $items      = Helpers\Instances::items($conditions);
        echo json_encode($items, JSON_UNESCAPED_UNICODE);
    }
}
