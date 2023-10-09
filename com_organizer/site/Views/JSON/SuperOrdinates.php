<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\JSON;

use Organizer\Helpers;
use Organizer\Helpers\Input;

/**
 * Class answers dynamic subject pool related queries
 */
class SuperOrdinates extends BaseView
{
    /**
     * loads model data into view context
     * @return void
     */
    public function display()
    {
        $subID = Input::getID();
        $type  = Input::getCMD('type');

        // Pending program ranges are dependant on selected programs.
        $programIDs    = Helpers\Input::getIntCollection('curricula');
        $programRanges = Helpers\Programs::getPrograms($programIDs);

        $options = Helpers\Pools::getSuperOptions($subID, $type, $programRanges);
        echo json_encode(implode('', $options), JSON_UNESCAPED_UNICODE);
    }
}
