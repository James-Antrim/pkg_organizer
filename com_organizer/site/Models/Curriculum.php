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
        if ($poolID = Input::integer('poolID')) {
            $ranges             = Helpers\Pools::rows($poolID);
            $curriculum['name'] = Helpers\Pools::name($poolID);
            $curriculum['type'] = 'pool';
            $curriculum         += array_pop($ranges);
            Helpers\Pools::curriculum($curriculum);
        }
        elseif ($programID = Input::integer('programID')) {
            $ranges             = Helpers\Programs::rows($programID);
            $curriculum['name'] = Helpers\Programs::name($programID);
            $curriculum['type'] = 'program';
            $curriculum         += array_pop($ranges);
            Helpers\Programs::curriculum($curriculum);
        }

        return $curriculum;
    }
}
