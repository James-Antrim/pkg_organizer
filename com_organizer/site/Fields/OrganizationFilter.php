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

use stdClass;
use THM\Organizer\Adapters\Application;
use THM\Organizer\Helpers\Organizations;

/**
 * Class creates a select box for organizations.
 */
class OrganizationFilter extends Options
{
    /**
     * Returns an array of options
     * @return stdClass[]  the organization options
     */
    protected function getOptions(): array
    {
        $options       = parent::getOptions();
        $access        = Application::backend() ? $this->getAttribute('access', '') : '';
        $organizations = Organizations::options(true, $access);

        return count($organizations) > 1 ? array_merge($options, $organizations) : $organizations;
    }
}
