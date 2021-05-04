<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Organizer\Helpers;

/**
 * Class loads curriculum information into the view context.
 */
class Curriculum extends ItemModel
{
    /**
     * Method to get an array of data items.
     *
     * @return mixed  An array of data items on success, false on failure.
     */
    public function getItem()
    {
        $curriculum = [];
        if ($poolID = Helpers\Input::getInt('poolID')) {
            $ranges             = Helpers\Pools::getRanges($poolID);
            $curriculum['name'] = Helpers\Pools::getName($poolID);
            $curriculum['type'] = 'pool';
            $curriculum         += array_pop($ranges);
            Helpers\Pools::getCurriculum($curriculum);
        } elseif ($programID = Helpers\Input::getInt('programID')) {
            $ranges             = Helpers\Programs::getRanges($programID);
            $curriculum['name'] = Helpers\Programs::getName($programID);
            $curriculum['type'] = 'program';
            $curriculum         += array_pop($ranges);
            Helpers\Programs::getCurriculum($curriculum);
        }

        return $curriculum;
    }
}
