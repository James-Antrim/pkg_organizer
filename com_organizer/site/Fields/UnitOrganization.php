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

use Joomla\CMS\Form\Field\ListField;
use THM\Organizer\{Adapters\HTML, Helpers\Organizations};

/** @inheritDoc */
class UnitOrganization extends ListField
{
    /** @inheritDoc */
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

    /** @inheritDoc */
    protected function getOptions(): array
    {
        $options       = parent::getOptions();
        $organizations = Organizations::options(true, 'teach');

        return count($organizations) > 1 ? array_merge($options, $organizations) : $organizations;
    }
}
