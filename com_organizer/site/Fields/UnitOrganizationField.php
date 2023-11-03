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
use THM\Organizer\Adapters\HTML;
use THM\Organizer\Helpers\Organizations;

/**
 * Class creates a select box for organizations.
 */
class UnitOrganizationField extends OptionsField
{
    /**
     * @var  string
     */
    protected $type = 'OrganizationFilter';

    /**
     * Method to get the field input markup for a generic list.
     * @return  string  The field input markup.
     */
    protected function getInput(): string
    {
        $onchange = $this->onchange ? ['onchange' => $this->onchange] : [];

        // Get the field options.
        $options = $this->getOptions();

        return HTML::selectBox(
            $this->name,
            $options,
            $this->value,
            $onchange
        );
    }

    /**
     * Returns an array of options
     * @return stdClass[]  the organization options
     */
    protected function getOptions(): array
    {
        $options       = parent::getOptions();
        $organizations = Organizations::getOptions(true, 'teach');

        return count($organizations) > 1 ? array_merge($options, $organizations) : $organizations;
    }
}
