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

use THM\Organizer\Helpers;
use stdClass;

/**
 * Class creates a form field for campus selection.
 */
class Campuses extends Options
{
    /**
     * @var  string
     */
    protected $type = 'Campuses';

    /**
     * Returns an array of options
     * @return stdClass[]  the options
     */
    protected function getOptions(): array
    {
        $options  = parent::getOptions();
        $campuses = Helpers\Campuses::getOptions();

        return array_merge($options, $campuses);
    }
}
