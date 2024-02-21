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
 * Class answers dynamic subject pool related queries
 */
class SuperOrdinates extends BaseView
{
    /**
     * loads model data into view context
     * @return void
     */
    public function display(): void
    {
        $subID = Input::getID();
        $type  = Input::getCMD('type');

        // Pending program ranges are dependent on selected programs.
        $programIDs    = Input::getIntArray('curricula');
        $programRanges = Helpers\Programs::programs($programIDs);

        $options = Helpers\Pools::superOptions($subID, $type, $programRanges);
        echo json_encode(implode('', $options), JSON_UNESCAPED_UNICODE);
    }
}
