<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Fields;

use THM\Organizer\Adapters\Input;
use THM\Organizer\Helpers;
use stdClass;

/**
 * Class creates a select box for (subject) pools.
 */
class Grids extends Options
{
    /**
     * Method to get the field input markup for a generic list.
     * @return  string  The field input markup.
     */
    protected function getInput(): string
    {
        if (empty($this->value) and $campusID = Input::getParams()->get('campusID')) {
            $this->value = Helpers\Campuses::gridID($campusID);
        }

        return parent::getInput();
    }

    /**
     * Returns an array of pool options
     * @return stdClass[]  the pool options
     */
    protected function getOptions(): array
    {
        $options  = parent::getOptions();
        $campuses = Helpers\Grids::options();

        return array_merge($options, $campuses);
    }
}
