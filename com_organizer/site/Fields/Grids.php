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
use THM\Organizer\{Adapters\Input, Helpers};

/** @inheritDoc */
class Grids extends ListField
{
    /** @inheritDoc */
    protected function getInput(): string
    {
        if (empty($this->value) and $campusID = Input::getParams()->get('campusID')) {
            $this->value = Helpers\Campuses::gridID($campusID);
        }

        return parent::getInput();
    }

    /** @inheritDoc */
    protected function getOptions(): array
    {
        $options  = parent::getOptions();
        $campuses = Helpers\Grids::options();

        return array_merge($options, $campuses);
    }
}
