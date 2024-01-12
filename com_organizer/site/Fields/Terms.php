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

use THM\Organizer\Helpers\Terms as Helper;

/**
 * Class creates a select box for terms.
 */
class Terms extends Options
{
    /**
     * Method to get the field options.
     * @return  array  The field option objects.
     */
    protected function getOptions(): array
    {
        $options   = parent::getOptions();
        $filter    = (bool) $this->getAttribute('filter');
        $withDates = (bool) $this->getAttribute('withDates');
        $terms     = Helper::options($withDates, $filter);

        return array_merge($options, $terms);
    }
}
