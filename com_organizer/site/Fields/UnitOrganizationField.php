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

use Organizer\Helpers;
use stdClass;

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
        $this->adminContext = Helpers\OrganizerHelper::getApplication()->isClient('administrator');
        $onchange           = $this->onchange ? ' onchange="' . $this->onchange . '"' : '';

        // Get the field options.
        $options = $this->getOptions();

        return Helpers\HTML::_(
            'select.genericlist',
            $options,
            $this->name,
            $onchange,
            'value',
            'text',
            $this->value,
            $this->id
        );
    }

    /**
     * Returns an array of options
     * @return stdClass[]  the organization options
     */
    protected function getOptions(): array
    {
        $options       = parent::getOptions();
        $organizations = Helpers\Organizations::getOptions(true, 'teach');

        return count($organizations) > 1 ? array_merge($options, $organizations) : $organizations;
    }
}
