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

use Organizer\Helpers;

/**
 * Class answers dynamic (degree) program related queries
 */
class CampusOptions extends BaseView
{
    /**
     * loads model data into view context
     * @return void
     */
    public function display()
    {
        echo json_encode(Helpers\Campuses::getOptions(), JSON_UNESCAPED_UNICODE);
    }
}
