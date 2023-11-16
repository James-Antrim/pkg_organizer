<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use THM\Organizer\Adapters\Input;
use THM\Organizer\Helpers;

/**
 * Class loads curriculum information into the view context.
 */
class Curriculum extends ItemModel
{
    /**
     * Method to get an array of data items.
     * @return mixed  An array of data items on success, false on failure.
     */
    public function getItem()
    {
        $curriculum = [];
        if ($poolID = Input::getInt('poolID')) {
            $ranges             = Helpers\Pools::ranges($poolID);
            $curriculum['name'] = Helpers\Pools::getName($poolID);
            $curriculum['type'] = 'pool';
            $curriculum         += array_pop($ranges);
            Helpers\Pools::curriculum($curriculum);
        }
        elseif ($programID = Input::getInt('programID')) {
            $ranges             = Helpers\Programs::ranges($programID);
            $curriculum['name'] = Helpers\Programs::getName($programID);
            $curriculum['type'] = 'program';
            $curriculum         += array_pop($ranges);
            Helpers\Programs::curriculum($curriculum);
        }

        return $curriculum;
    }
}
