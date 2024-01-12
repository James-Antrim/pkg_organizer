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

use THM\Organizer\Adapters\Application;
use THM\Organizer\Helpers;
use stdClass;

/**
 * Class creates a select box for (subject) pools.
 */
class Pools extends Options
{
    /**
     * Returns an array of pool options
     * @return stdClass[]  the pool options
     */
    protected function getOptions(): array
    {
        $options = parent::getOptions();
        $access  = Application::backend() ? 'document' : '';
        $pools   = Helpers\Pools::options($access);

        return array_merge($options, $pools);
    }
}
