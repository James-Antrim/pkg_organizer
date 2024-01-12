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
 * Class creates a select box for plan programs.
 */
class Categories extends Options
{
    /**
     * Returns a select box where resource attributes can be selected
     * @return stdClass[] the options for the select box
     */
    protected function getOptions(): array
    {
        $options    = parent::getOptions();
        $categories = Helpers\Categories::options();

        return array_merge($options, $categories);
    }
}
